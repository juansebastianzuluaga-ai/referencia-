<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class NotificationController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Notification::where('user_id', $request->user()->id)
            ->latest();

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
        $count = Notification::where('user_id', $request->user()->id)
            ->unread()
            ->count();

        return $this->sendResponse(['count' => $count]);
    }

    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
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
        Notification::where('user_id', $request->user()->id)
            ->unread()
            ->update(['read_at' => now()]);

        return $this->sendResponse(new \stdClass, 'Todas las notificaciones marcadas como leidas');
    }

    public function destroy(Request $request, Notification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            return $this->sendError('No autorizado', code: Response::HTTP_FORBIDDEN);
        }

        $notification->delete();

        return $this->sendResponse(new \stdClass, 'Notificacion eliminada correctamente');
    }

    public function clearAll(Request $request): JsonResponse
    {
        Notification::where('user_id', $request->user()->id)->delete();

        return $this->sendResponse(new \stdClass, 'Notificaciones eliminadas correctamente');
    }
}
