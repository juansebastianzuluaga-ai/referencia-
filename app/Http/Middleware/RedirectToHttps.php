<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirige todo el tráfico HTTP a HTTPS. Sin esto, las cookies de sesión
 * marcadas Secure (emitidas cuando alguien entra por https) nunca llegan
 * al servidor cuando el usuario vuelve por http — el navegador no las
 * envía ni deja reemplazarlas — y el login parece "exito" pero la sesión
 * no persiste. Forzando https, las cookies siempre viajan.
 */
class RedirectToHttps
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isSecure() && $request->header('X-Forwarded-Proto') !== 'https') {
            // 308 (no 301): conserva método y body — los POST de la API se
            // reenvían intactos a https en vez de convertirse en GET.
            return redirect()->to(
                'https://'.$request->getHttpHost().$request->getRequestUri(),
                308,
            );
        }

        return $next($request);
    }
}
