<?php

namespace App\Http\Controllers\Api\Clinica;

use App\Http\Controllers\Api\BaseController;
use App\Http\Resources\NotificationResource;
use App\Models\ClinicaSession;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class NotificationController extends BaseController
{
    private function getClinicaId(Request $request): ?int
    {
        $token = $request->bearerToken() ?? $request->header('X-Clinica-Token');

        if (! $token) {
            return null;
        }

        $session = ClinicaSession::where('token', hash('sha256', $token))
            ->where(function ($q): void {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        return $session?->clinica_id;
    }

    public function index(Request $request): JsonResponse
    {
        $clinicaId = $this->getClinicaId($request);

        if (! $clinicaId) {
            return $this->sendError('No autenticado', code: Response::HTTP_UNAUTHORIZED);
        }

        $query = Notification::where('clinica_id', $clinicaId)->latest();

        if ($request->boolean('unread_only')) {
            $query->unread();
        }

        $perPage = (int) $request->integer('per_page', 10);
        $notifications = $query->paginate($perPage);

        return $this->sendResponse(
            NotificationResource::collection($notifications),
            'Notificaciones consultadas correctamente',
        );
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $clinicaId = $this->getClinicaId($request);

        if (! $clinicaId) {
            return $this->sendError('No autenticado', code: Response::HTTP_UNAUTHORIZED);
        }

        $count = Notification::where('clinica_id', $clinicaId)->unread()->count();

        return $this->sendResponse(['count' => $count]);
    }

    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        $clinicaId = $this->getClinicaId($request);

        if (! $clinicaId || $notification->clinica_id !== $clinicaId) {
            return $this->sendError('No autorizado', code: Response::HTTP_FORBIDDEN);
        }

        $notification->markAsRead();

        return $this->sendResponse(
            NotificationResource::make($notification),
            'Notificacion marcada como leida',
        );
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $clinicaId = $this->getClinicaId($request);

        if (! $clinicaId) {
            return $this->sendError('No autenticado', code: Response::HTTP_UNAUTHORIZED);
        }

        Notification::where('clinica_id', $clinicaId)->unread()->update(['read_at' => now()]);

        return $this->sendResponse(new \stdClass, 'Todas las notificaciones marcadas como leidas');
    }

    public function destroy(Request $request, Notification $notification): JsonResponse
    {
        $clinicaId = $this->getClinicaId($request);

        if (! $clinicaId || $notification->clinica_id !== $clinicaId) {
            return $this->sendError('No autorizado', code: Response::HTTP_FORBIDDEN);
        }

        $notification->delete();

        return $this->sendResponse(new \stdClass, 'Notificacion eliminada correctamente');
    }

    public function clearAll(Request $request): JsonResponse
    {
        $clinicaId = $this->getClinicaId($request);

        if (! $clinicaId) {
            return $this->sendError('No autenticado', code: Response::HTTP_UNAUTHORIZED);
        }

        Notification::where('clinica_id', $clinicaId)->delete();

        return $this->sendResponse(new \stdClass, 'Notificaciones eliminadas correctamente');
    }
}
