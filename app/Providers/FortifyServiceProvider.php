<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Controllers\Api\BaseController;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\LogoutResponse;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponse::class, fn () => new class implements LoginResponse
        {
            public function toResponse($request)
            {
                return app(BaseController::class)->sendResponse(
                    UserResource::make($request->user()->load(['identificationType', 'roles.permissions'])),
                    'Inicio de sesion exitoso',
                    Response::HTTP_OK,
                );
            }
        });

        $this->app->singleton(LogoutResponse::class, fn () => new class implements LogoutResponse
        {
            public function toResponse($request)
            {
                return app(BaseController::class)->sendResponse(new \stdClass, 'Sesion cerrada correctamente');
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);
        Fortify::authenticateUsing(function (Request $request): ?User {
            $user = User::query()
                ->where('user_name', $request->input('username'))
                ->first();

            if (! $user || ! $user->is_active || ! Hash::check($request->input('password'), $user->password)) {
                return null;
            }

            $user->forceFill(['last_login_at' => now()])->save();

            return $user;
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('passkeys', function (Request $request) {
            $credentialId = $request->input('credential.id');

            return Limit::perMinute(10)->by(
                ($credentialId ?: $request->session()->getId()).'|'.$request->ip()
            );
        });

        // Se limita por NIT de la clínica (el objetivo del ataque), no solo
        // por IP: un atacante puede rotar de IP, pero no de NIT. Con 5
        // intentos por minuto, en los 10 minutos que dura el código OTP
        // como máximo se prueban ~50 de las 1,000,000 combinaciones posibles.
        RateLimiter::for('otp-verify', function (Request $request) {
            $nit = preg_replace('/\D/', '', (string) $request->input('nit'));

            return Limit::perMinute(5)->by('otp-verify|'.$nit);
        });

        // Evita que se spamee la solicitud de un código nuevo (cada solicitud
        // invalida el código anterior no usado, así que sin límite se podría
        // bloquear a una clínica legítima además de saturar SMS/correo).
        RateLimiter::for('otp-request', function (Request $request) {
            $nit = preg_replace('/\D/', '', (string) $request->input('nit'));

            return Limit::perMinutes(10, 3)->by('otp-request|'.$nit.'|'.$request->ip());
        });
    }
}
