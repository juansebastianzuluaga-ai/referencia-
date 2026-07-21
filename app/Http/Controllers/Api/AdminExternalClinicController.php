<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\FiltersPaginatedResults;
use App\Http\Requests\ApproveExternalClinicRequest;
use App\Http\Requests\RejectExternalClinicRequest;
use App\Http\Resources\ExternalClinicDocumentResource;
use App\Http\Resources\ExternalClinicRequestResource;
use App\Http\Resources\ExternalClinicResource;
use App\Models\ExternalClinic;
use App\Notifications\ExternalClinicApproved;
use App\Notifications\ExternalClinicRejected;
use App\Services\ActivityLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminExternalClinicController extends BaseController
{
    use FiltersPaginatedResults;

    /**
     * @var array<int, string>
     */
    protected array $searchable = [
        'nit',
        'business_name',
        'trade_name',
        'email',
        'phone',
        'city',
        'department',
        'legal_rep_name',
        'legal_rep_id_number',
    ];

    /**
     * @var array<int, string>
     */
    protected array $filterable = [
        'status',
        'city',
        'department',
    ];

    /**
     * @var array<int, string>
     */
    protected array $sortable = [
        'id',
        'nit',
        'business_name',
        'city',
        'status',
        'created_at',
        'updated_at',
    ];

    public function __construct(
        private ActivityLogger $activityLogger,
    ) {
    }

    public function getAll(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasPermission('external-clinics.view'), Response::HTTP_FORBIDDEN);

        $clinics = $this->filterPaginated(
            query: ExternalClinic::query()->with(['legalRepIdentificationType']),
            request: $request,
            searchable: $this->searchable,
            filterable: $this->filterable,
            sortable: $this->sortable,
            defaultPageSize: 10,
        );

        return $this->sendResponse(
            ExternalClinicResource::collection($clinics),
            'Clínicas externas consultadas correctamente',
        );
    }

    public function show(Request $request, ExternalClinic $clinic): JsonResponse
    {
        abort_unless($request->user()->hasPermission('external-clinics.view'), Response::HTTP_FORBIDDEN);

        $clinic->load(['legalRepIdentificationType', 'documents', 'requests.changedBy', 'approver', 'rejecter']);

        return $this->sendResponse(
            new ExternalClinicResource($clinic),
            'Clínica externa consultada correctamente',
        );
    }

    public function approve(ApproveExternalClinicRequest $request, ExternalClinic $clinic): JsonResponse
    {
        if (! $clinic->isPending()) {
            return $this->sendError(
                'Solo se pueden aprobar solicitudes pendientes.',
                code: Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $previousStatus = $clinic->status;
        $reason = $request->validated('change_reason');
        $user = $request->user();

        $clinic->approve($user);
        $clinic->activate();

        $clinic->requests()->create([
            'previous_status' => $previousStatus,
            'new_status' => $clinic->status,
            'changed_by' => $user->id,
            'change_reason' => $reason,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $this->activityLogger->log(
            event: 'external_clinic_approve',
            module: 'external_clinics',
            description: 'Solicitud aprobada: '.$clinic->business_name,
            properties: ['clinic_id' => $clinic->id, 'nit' => $clinic->nit],
            user: $user,
        );

        $clinic->notify(new ExternalClinicApproved($clinic));

        return $this->sendResponse(
            new ExternalClinicResource($clinic),
            'Solicitud aprobada correctamente.',
        );
    }

    public function reject(RejectExternalClinicRequest $request, ExternalClinic $clinic): JsonResponse
    {
        if (! $clinic->isPending()) {
            return $this->sendError(
                'Solo se pueden rechazar solicitudes pendientes.',
                code: Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $previousStatus = $clinic->status;
        $reason = $request->validated('rejection_reason');
        $user = $request->user();

        $clinic->reject($user, $reason);

        $clinic->requests()->create([
            'previous_status' => $previousStatus,
            'new_status' => $clinic->status,
            'changed_by' => $user->id,
            'change_reason' => $reason,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $this->activityLogger->log(
            event: 'external_clinic_reject',
            module: 'external_clinics',
            description: 'Solicitud rechazada: '.$clinic->business_name,
            properties: ['clinic_id' => $clinic->id, 'nit' => $clinic->nit],
            user: $user,
        );

        $clinic->notify(new ExternalClinicRejected($clinic));

        return $this->sendResponse(
            new ExternalClinicResource($clinic),
            'Solicitud rechazada correctamente.',
        );
    }

    public function toggleStatus(Request $request, ExternalClinic $clinic): JsonResponse
    {
        abort_unless($request->user()->hasPermission('external-clinics.manage'), Response::HTTP_FORBIDDEN);

        if (! in_array($clinic->status, ['active', 'inactive'])) {
            return $this->sendError(
                'Solo se pueden activar/desactivar cuentas en estado activo o inactivo.',
                code: Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $previousStatus = $clinic->status;
        $user = $request->user();

        if ($clinic->isActive()) {
            $clinic->deactivate();
        } else {
            $clinic->activate();
        }

        $clinic->requests()->create([
            'previous_status' => $previousStatus,
            'new_status' => $clinic->status,
            'changed_by' => $user->id,
            'change_reason' => $request->input('reason'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $this->activityLogger->log(
            event: 'external_clinic_toggle_status',
            module: 'external_clinics',
            description: 'Cuenta '.$clinic->status.': '.$clinic->business_name,
            properties: ['clinic_id' => $clinic->id, 'nit' => $clinic->nit],
            user: $user,
        );

        return $this->sendResponse(
            new ExternalClinicResource($clinic),
            'Estado de la cuenta actualizado correctamente.',
        );
    }

    public function documents(Request $request, ExternalClinic $clinic): JsonResponse
    {
        abort_unless($request->user()->hasPermission('external-clinics.view'), Response::HTTP_FORBIDDEN);

        return $this->sendResponse(
            ExternalClinicDocumentResource::collection($clinic->documents),
            'Documentos consultados correctamente',
        );
    }

    public function downloadDocument(Request $request, int $clinicId, int $documentId): mixed
    {
        abort_unless($request->user()->hasPermission('external-clinics.view'), Response::HTTP_FORBIDDEN);

        $document = ExternalClinic::findOrFail($clinicId)->documents()->find($documentId);

        if (! $document) {
            throw new NotFoundHttpException('Documento no encontrado.');
        }

        if (! Storage::exists($document->file_path)) {
            throw new NotFoundHttpException('El archivo no existe en el servidor.');
        }

        return Storage::download($document->file_path, $document->file_name);
    }
}
