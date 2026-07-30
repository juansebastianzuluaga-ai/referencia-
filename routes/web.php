<?php

use App\Http\Controllers\Api\ApiCredentialController;
use App\Http\Controllers\Api\Clinica\LoginExternoController;
use App\Http\Controllers\Api\Clinica\SolicitudReferenciaController as SolicitudReferenciaExternoController;
use App\Http\Controllers\Api\ClinicaController;
use App\Http\Controllers\Api\CurrentUserController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\IdentificationTypeController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\SolicitudReferenciaController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Named login route required by Laravel's auth redirect mechanism
Route::get('/login', fn () => view('welcome'))->name('login');

Route::prefix('api')->name('api.')->group(function (): void {

    // ── Login externo para clínicas (sin Sanctum) ────────────────────────────
    Route::prefix('externo')->name('externo.')->group(function () {
        Route::post('buscar-clinica', [LoginExternoController::class, 'buscarClinica'])->name('buscar-clinica');
        Route::post('solicitar-acceso', [LoginExternoController::class, 'solicitarAcceso'])->name('solicitar-acceso');
        Route::post('verificar-otp', [LoginExternoController::class, 'verificarOtp'])->name('verificar-otp');
        Route::post('verificar-magic-link', [LoginExternoController::class, 'verificarMagicLink'])->name('verificar-magic-link');
        Route::post('registro', [LoginExternoController::class, 'registro'])->name('registro');
        Route::get('clinica', [LoginExternoController::class, 'clinicaActual'])->name('clinica');
        Route::post('logout', [LoginExternoController::class, 'logout'])->name('logout');
        Route::get('solicitudes', [SolicitudReferenciaExternoController::class, 'index'])->name('solicitudes.index');
        Route::post('solicitudes', [SolicitudReferenciaExternoController::class, 'store'])->name('solicitudes.store');
        Route::get('solicitudes/{id}', [SolicitudReferenciaExternoController::class, 'show'])->name('solicitudes.show');
        Route::get('solicitudes/{solicitudId}/adjuntos/{adjuntoId}/descargar', [SolicitudReferenciaExternoController::class, 'descargarAdjunto'])->name('solicitudes.adjuntos.descargar');
    });
    // ────────────────────────────────────────────────────────────────────────

    Route::get('user/password-policy', [CurrentUserController::class, 'passwordPolicy'])->name('user.password-policy');

    Route::middleware(['auth:sanctum', 'active'])->group(function (): void {
        Route::get('dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');

        Route::get('user', CurrentUserController::class)->name('user');
        Route::put('user/profile', [CurrentUserController::class, 'updateProfile'])->name('user.profile.update');
        Route::put('user/password', [CurrentUserController::class, 'updatePassword'])->name('user.password.update');

        Route::post('users/get-all', [UserController::class, 'getAll'])->name('users.get-all');
        Route::put('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::apiResource('users', UserController::class)->except(['index']);

        Route::post('identification-types/get-all', [IdentificationTypeController::class, 'getAll'])->name('identification-types.get-all');
        Route::apiResource('identification-types', IdentificationTypeController::class)->except(['index']);

        Route::post('roles/get-all', [RoleController::class, 'getAll'])->name('roles.get-all');
        Route::apiResource('roles', RoleController::class)->except(['index']);

        Route::post('permissions/get-all', [PermissionController::class, 'getAll'])->name('permissions.get-all');

        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

        Route::get('api-credentials', [ApiCredentialController::class, 'index'])->name('api-credentials.index');
        Route::post('api-credentials', [ApiCredentialController::class, 'store'])->name('api-credentials.store');
        Route::put('api-credentials/{id}', [ApiCredentialController::class, 'update'])->name('api-credentials.update');
        Route::delete('api-credentials/{id}', [ApiCredentialController::class, 'destroy'])->name('api-credentials.destroy');
        Route::post('api-credentials/{id}/regenerate', [ApiCredentialController::class, 'regenerate'])->name('api-credentials.regenerate');
        Route::patch('api-credentials/{id}/toggle-status', [ApiCredentialController::class, 'toggleStatus'])->name('api-credentials.toggle-status');
        Route::post('api-credentials/{id}/revoke', [ApiCredentialController::class, 'revoke'])->name('api-credentials.revoke');
        Route::get('api-credentials/{id}/logs', [ApiCredentialController::class, 'logs'])->name('api-credentials.logs');
        Route::get('api-credentials/{id}/stats', [ApiCredentialController::class, 'stats'])->name('api-credentials.stats');

        Route::get('clinicas', [ClinicaController::class, 'index'])->name('clinicas.index');
        Route::post('clinicas/{clinica}/aprobar', [ClinicaController::class, 'aprobar'])->name('clinicas.aprobar');
        Route::post('clinicas/{clinica}/rechazar', [ClinicaController::class, 'rechazar'])->name('clinicas.rechazar');
        Route::post('clinicas/{clinica}/reactivar', [ClinicaController::class, 'reactivar'])->name('clinicas.reactivar');

        Route::get('solicitudes-referencia', [SolicitudReferenciaController::class, 'index'])->name('solicitudes-referencia.index');
        Route::get('solicitudes-referencia/{solicitud}', [SolicitudReferenciaController::class, 'show'])->name('solicitudes-referencia.show');
        Route::post('solicitudes-referencia/{solicitud}/aceptar', [SolicitudReferenciaController::class, 'aceptar'])->name('solicitudes-referencia.aceptar');
        Route::post('solicitudes-referencia/{solicitud}/negar', [SolicitudReferenciaController::class, 'negar'])->name('solicitudes-referencia.negar');
        Route::post('solicitudes-referencia/{solicitud}/pendiente', [SolicitudReferenciaController::class, 'pendiente'])->name('solicitudes-referencia.pendiente');
        Route::post('solicitudes-referencia/{solicitud}/en-espera', [SolicitudReferenciaController::class, 'enEspera'])->name('solicitudes-referencia.en-espera');
        Route::post('solicitudes-referencia/{solicitud}/completado', [SolicitudReferenciaController::class, 'completado'])->name('solicitudes-referencia.completado');
        Route::get('solicitudes-referencia/{solicitud}/adjuntos/{adjunto}/descargar', [SolicitudReferenciaController::class, 'descargarAdjunto'])->name('solicitudes-referencia.adjuntos.descargar');

        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
        Route::patch('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::patch('notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
        Route::delete('notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
        Route::delete('notifications', [NotificationController::class, 'clearAll'])->name('notifications.clear-all');
    });
});

Route::view('/{any}', 'welcome')
    ->where('any', '^(?!api|sanctum).*$');
