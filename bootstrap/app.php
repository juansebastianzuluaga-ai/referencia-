<?php

use App\Http\Middleware\ApiAuthMiddleware;
use App\Http\Middleware\EnsureUserHasPermission;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();

        $middleware->validateCsrfTokens(except: [
            'api/login',
            'api/logout',
            'api/forgot-password',
            'api/reset-password',
        ]);

        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'permission' => EnsureUserHasPermission::class,
            'api-auth' => ApiAuthMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $code = match (true) {
                $exception instanceof AuthenticationException => 401,
                $exception instanceof AuthorizationException => 403,
                $exception instanceof ValidationException => 422,
                $exception instanceof HttpExceptionInterface => $exception->getStatusCode(),
                default => 500,
            };

            $data = match (true) {
                $exception instanceof ValidationException => ['errors' => $exception->errors()],
                default => [],
            };

            if (config('app.debug')) {
                $data['exception'] = [
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ];
            }

            return response()->json([
                'code' => $code,
                'success' => false,
                'message' => $exception instanceof ValidationException
                    ? 'Los datos enviados no son validos'
                    : ($exception->getMessage() ?: 'Error en la solicitud'),
                'data' => empty($data) ? new stdClass : $data,
            ], $code);
        });
    })->create();
