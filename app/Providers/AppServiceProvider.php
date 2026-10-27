<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
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
        // Paksa HTTPS di production agar URL aset dan redirect konsisten.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Gerbang akses dokumentasi Scramble di /docs/api.
        // Dibuka publik agar penguji dapat membuka dokumentasi tanpa token.
        Gate::define('viewApiDocs', fn ($user = null) => true);
    }
}
