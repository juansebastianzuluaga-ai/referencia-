<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureExternalClinic
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $clinic = $request->user('external_clinic');

        if (! $clinic) {
            return response()->json([
                'code' => 401,
                'success' => false,
                'message' => 'No autenticado como clínica externa',
                'data' => new \stdClass,
            ], 401);
        }

        if ($clinic->status !== 'active') {
            $messages = [
                'pending' => 'Su solicitud está pendiente de aprobación. Le notificaremos por correo cuando sea revisada.',
                'approved' => 'Su cuenta ha sido aprobada pero aún no está activa. Contacte al administrador.',
                'rejected' => 'Su solicitud fue rechazada. Motivo: '.$clinic->rejection_reason,
                'inactive' => 'Su cuenta está inactiva. Contacte al administrador para más información.',
            ];

            return response()->json([
                'code' => 403,
                'success' => false,
                'message' => $messages[$clinic->status] ?? 'Su cuenta no está activa',
                'data' => [
                    'status' => $clinic->status,
                    'rejection_reason' => $clinic->rejection_reason,
                ],
            ], 403);
        }

        return $next($request);
    }
}
