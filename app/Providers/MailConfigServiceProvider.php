<?php

namespace App\Providers;

use App\Services\MailConfigService;
use Illuminate\Support\ServiceProvider;

class MailConfigServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Configurar mailer dinámicamente desde settings de BD
        if ($this->app->runningInConsole() && ! $this->app->runningUnitTests()) {
            return;
        }

        try {
            $mailConfigService = app(MailConfigService::class);
            $mailConfigService->configure();
        } catch (\Exception $e) {
            // Si falla (ej: BD no disponible), usar configuración por defecto
            // Esto evita errores durante migraciones o cuando la BD no está lista
        }
    }
}
