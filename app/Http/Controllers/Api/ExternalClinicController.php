<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\UpdateExternalClinicPasswordRequest;
use App\Http\Requests\UpdateExternalClinicProfileRequest;
use App\Http\Resources\ExternalClinicResource;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ExternalClinicController extends BaseController
{
    public function __construct(
        private ActivityLogger $activityLogger,
    ) {
    }

    public function profile(Request $request): JsonResponse
    {
        $clinic = $request->user('external_clinic');
        $clinic->load('legalRepIdentificationType');

        return $this->sendResponse(
            new ExternalClinicResource($clinic),
            'Perfil consultado correctamente.',
        );
    }

    public function updateProfile(UpdateExternalClinicProfileRequest $request): JsonResponse
    {
        $clinic = $request->user('external_clinic');
        $clinic->update($request->validated());

        $clinic->load('legalRepIdentificationType');

        $this->activityLogger->log(
            event: 'external_clinic_profile_update',
            module: 'external_clinics',
            description: 'Perfil actualizado: '.$clinic->business_name,
            properties: ['clinic_id' => $clinic->id],
        );

        return $this->sendResponse(
            new ExternalClinicResource($clinic),
            'Perfil actualizado correctamente.',
        );
    }

    public function updatePassword(UpdateExternalClinicPasswordRequest $request): JsonResponse
    {
        $clinic = $request->user('external_clinic');

        $clinic->update([
            'password' => Hash::make($request->validated('password')),
            'must_change_password' => false,
        ]);

        $this->activityLogger->log(
            event: 'external_clinic_password_update',
            module: 'external_clinics',
            description: 'Contraseña actualizada: '.$clinic->business_name,
            properties: ['clinic_id' => $clinic->id],
        );

        Auth::guard('external_clinic')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->sendResponse(
            new \stdClass,
            'Contraseña actualizada correctamente. Inicie sesión nuevamente.',
        );
    }
}
