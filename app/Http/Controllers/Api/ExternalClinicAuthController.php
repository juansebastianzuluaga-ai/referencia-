<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\LoginExternalClinicRequest;
use App\Http\Requests\RegisterExternalClinicRequest;
use App\Http\Resources\ExternalClinicResource;
use App\Models\ExternalClinic;
use App\Models\Notification;
use App\Models\User;
use App\Notifications\ExternalClinicRegistered;
use App\Notifications\NewExternalClinicRequest;
use App\Services\ActivityLogger;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ExternalClinicAuthController extends BaseController
{
    public function __construct(
        private ActivityLogger $activityLogger,
    ) {
    }

    public function register(RegisterExternalClinicRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $clinic = DB::transaction(function () use ($validated, $request): ExternalClinic {
            $documents = $request->file('documents', []);
            unset($validated['documents'], $validated['terms_accepted']);

            $clinic = ExternalClinic::create($validated);

            $this->storeDocuments($clinic, $documents);
            $this->createRequestLog($clinic, null, 'pending', null, $request);

            return $clinic;
        });

        $clinic->load('legalRepIdentificationType');

        $this->activityLogger->log(
            event: 'external_clinic_register',
            module: 'external_clinics',
            description: 'Nueva solicitud de clínica externa: '.$clinic->business_name,
            properties: ['clinic_id' => $clinic->id, 'nit' => $clinic->nit],
        );

        $clinic->notify(new ExternalClinicRegistered($clinic));
        $this->notifyAdmins(new NewExternalClinicRequest($clinic));

        return $this->sendResponse(
            new ExternalClinicResource($clinic),
            'Solicitud de registro enviada correctamente.',
        );
    }

    public function checkNit(Request $request): JsonResponse
    {
        $request->validate([
            'nit' => ['required', 'string', 'max:20'],
        ]);

        $exists = ExternalClinic::where('nit', $request->string('nit'))->exists();

        return $this->sendResponse(
            ['available' => ! $exists],
            $exists ? 'El NIT ya está registrado.' : 'El NIT está disponible.',
        );
    }

    public function login(LoginExternalClinicRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $clinic = ExternalClinic::where('nit', $validated['nit'])->first();

        if (! $clinic || ! Hash::check($validated['password'], $clinic->password)) {
            if ($clinic) {
                $clinic->increment('failed_login_attempts');
            }

            throw new AuthenticationException('NIT o contraseña incorrectos.');
        }

        if (! in_array($clinic->status, ['approved', 'active'])) {
            throw new AuthenticationException('Su cuenta no está activa.');
        }

        if ($clinic->status === 'approved') {
            $clinic->activate();
        }

        Auth::guard('external_clinic')->login($clinic, $validated['remember'] ?? false);

        $clinic->update([
            'last_login_at' => now(),
            'failed_login_attempts' => 0,
        ]);

        $clinic->load('legalRepIdentificationType');

        $this->activityLogger->log(
            event: 'external_clinic_login',
            module: 'external_clinics',
            description: 'Inicio de sesión de clínica externa: '.$clinic->business_name,
            properties: ['clinic_id' => $clinic->id, 'nit' => $clinic->nit],
        );

        return $this->sendResponse(
            new ExternalClinicResource($clinic),
            'Sesión iniciada correctamente.',
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $clinic = $request->user('external_clinic');

        Auth::guard('external_clinic')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($clinic) {
            $this->activityLogger->log(
                event: 'external_clinic_logout',
                module: 'external_clinics',
                description: 'Cierre de sesión de clínica externa: '.$clinic->business_name,
                properties: ['clinic_id' => $clinic->id, 'nit' => $clinic->nit],
            );
        }

        return $this->sendResponse(
            new \stdClass,
            'Sesión cerrada correctamente.',
        );
    }

    private function notifyAdmins(NewExternalClinicRequest $notification): void
    {
        $payload = $notification->toArray(new \stdClass());

        User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['super-admin', 'admin']))
            ->where('is_active', true)
            ->chunkById(100, function ($users) use ($payload, $notification): void {
                if ($users->isEmpty()) {
                    Log::warning('No se encontraron administradores para notificar nueva clínica externa.');

                    return;
                }

                foreach ($users as $user) {
                    Log::info('Notificando a administrador sobre nueva clínica externa.', [
                        'admin_id' => $user->id,
                        'admin_email' => $user->email,
                        'clinic_id' => $notification->clinic->id,
                    ]);

                    Notification::create([
                        'user_id' => $user->id,
                        'type' => 'info',
                        'title' => 'Nueva solicitud de clínica externa',
                        'message' => $payload['message'],
                        'link' => $payload['url'],
                    ]);

                    $user->notify($notification);
                }
            });
    }

    /**
     * @param  array<int, \Illuminate\Http\UploadedFile>  $documents
     */
    private function storeDocuments(ExternalClinic $clinic, array $documents): void
    {
        foreach ($documents as $document) {
            $path = Storage::disk('local')->putFile('external_clinic_documents/'.$clinic->id, $document);

            $clinic->documents()->create([
                'document_type' => 'other',
                'file_name' => $document->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $document->getSize(),
                'mime_type' => $document->getMimeType(),
            ]);
        }
    }

    private function createRequestLog(
        ExternalClinic $clinic,
        ?string $previousStatus,
        string $newStatus,
        ?string $reason,
        Request $request,
    ): void {
        $clinic->requests()->create([
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'change_reason' => $reason,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
