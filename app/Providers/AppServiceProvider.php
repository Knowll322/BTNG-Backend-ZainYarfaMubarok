<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityRequirement;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
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
         *
         * Security dipasang per operasi, bukan sekali di akar dokumen.
         * Kalau dipasang di akar, Scramble akan menyatakan bahwa rute GET
         * juga memerlukan token, padahal rute baca sengaja dibuka publik.
         */
        Scramble::afterOpenApiGenerated(function (OpenApi $openApi): void {
            $skema = SecurityScheme::http('bearer', 'Bearer')->as('sanctum');

            // addSecurityScheme() tidak mengembalikan nilai, jadi dipanggil
            // terpisah — jangan dibungkus setComponents(...).
            $openApi->components->addSecurityScheme('sanctum', $skema);

            /*
             * Tanda "butuh token" diambil dari middleware rute yang
             * sesungguhnya, bukan ditebak dari metode HTTP.
             *
             * Cara menebak salah pada dua titik: POST /auth/login dan
             * POST /auth/register justru publik (di situlah token
             * diperoleh), sedangkan GET /auth/me menuntut token. Dengan
             * membaca middleware, dokumentasi selalu cocok dengan
             * routes/api.php tanpa perlu diperbarui manual.
             */
            $bertoken = [];

            foreach (Route::getRoutes() as $rute) {
                $punyaSanctum = false;

                foreach ($rute->gatherMiddleware() as $m) {
                    if (is_string($m) && str_starts_with($m, 'auth:sanctum')) {
                        $punyaSanctum = true;
                        break;
                    }
                }

                if (! $punyaSanctum) {
                    continue;
                }

                /*
                 * Scramble menyimpan path tanpa awalan "api/" (mis. "mobil",
                 * "auth/login"), sedangkan $rute->uri() menyertakannya.
                 * Keduanya disamakan dulu supaya kunci dapat dicocokkan.
                 */
                $uri = preg_replace('#^api/#', '', $rute->uri());

                foreach ($rute->methods() as $metode) {
                    $bertoken[strtolower($metode).' '.$uri] = true;
                }
            }

            foreach ($openApi->paths as $path) {
                foreach ($path->operations as $operation) {
                    if (! $operation instanceof \Dedoc\Scramble\Support\Generator\Operation) {
                        continue;
                    }

                    $kunci = strtolower($operation->method).' '.$path->path;

                    if (isset($bertoken[$kunci])) {
                        $operation->addSecurity(new SecurityRequirement('sanctum'));
                    }
                }
            }
        });
    }
}
