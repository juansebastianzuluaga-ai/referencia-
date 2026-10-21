<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Expira duplicados viejos de las cookies de sesión/XSRF que quedaron
 * creadas con Domain=referencia.test. El navegador envía la variante con
 * dominio junto a la host-only actual y Laravel puede leer la vieja, lo que
 * produce "La sesión no se pudo establecer" después de un login exitoso.
 */
class ExpireStaleSessionCookies
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $diagnose = $request->is('api/user') || ($request->isMethod('POST') && $request->is('api/login'));

        try {
            $response = $next($request);
        } catch (Throwable $e) {
            if ($diagnose) {
                $this->logRequest($request, $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500);
            }

            throw $e;
        }

        if (
            ($request->isMethod('POST') && $request->is('api/login')) ||
            ($request->isMethod('GET') && $request->is('sanctum/csrf-cookie'))
        ) {
            $host = $request->getHost();
            $response->headers->clearCookie(config('session.cookie'), '/', $host);
            $response->headers->clearCookie('XSRF-TOKEN', '/', $host);
        }

        if ($diagnose) {
            $this->logRequest($request, $response->getStatusCode());
        }

        return $response;
    }

    private function logRequest(Request $request, int $status): void
    {
        Log::info('auth-debug', [
            'path' => $request->path(),
            'method' => $request->method(),
            'status' => $status,
            'cookie_names' => array_keys($request->cookies->all()),
            'has_session_cookie' => $request->hasCookie(config('session.cookie')),
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
            'authenticated' => $request->user() !== null,
            'secure' => $request->isSecure(),
            'host' => $request->getHost(),
        ]);
    }
}
