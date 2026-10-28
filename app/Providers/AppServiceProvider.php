<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
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

        /*
         * Tombol "Authorize" pada UI Scramble untuk token Bearer Sanctum.
         *
         * Tanpa ini penguji tidak punya cara mengisi token lewat antarmuka,
         * padahal seluruh rute tulis kini dijaga auth:sanctum. Akibatnya
         * "Try it out" pada POST/PUT/PATCH/DELETE selalu berakhir 401.
         */
        Scramble::afterOpenApiGenerated(function (OpenApi $openApi): void {
            $openApi->secure(
                SecurityScheme::http('bearer', 'Bearer')
            );
        });
    }
}
