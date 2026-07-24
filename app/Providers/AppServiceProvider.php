<?php

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $hotFile = public_path('hot');

        if (! File::exists($hotFile)) {
            return;
        }

        $hotUrl = trim(File::get($hotFile));
        $host = parse_url($hotUrl, PHP_URL_HOST);

        if (! in_array($host, ['127.0.0.1', 'localhost'], true)) {
            return;
        }

        try {
            $isViteAvailable = Http::connectTimeout(1)
                ->timeout(1)
                ->get(rtrim($hotUrl, '/').'/@vite/client')
                ->successful();
        } catch (\Throwable) {
            $isViteAvailable = false;
        }

        if (! $isViteAvailable) {
            File::delete($hotFile);
        }
    }
}
