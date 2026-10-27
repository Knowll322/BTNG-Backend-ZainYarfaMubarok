<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        /*
         * Semua respons API memakai amplop yang sama: { message, data, errors? }.
         * Ini menjaga konsistensi walau error terjadi sebelum Controller dijalankan
         * (route tidak ditemukan, model tidak ditemukan, method tidak diizinkan, dsb).
         */
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson()
        );

        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            $errors = $e->errors();
            $jumlah = count($errors);
            $pesan = $e->getMessage();

            return response()->json([
                'message' => $jumlah > 1 ? "{$pesan} (and ".($jumlah - 1).' more error)' : $pesan,
                'data' => null,
                'errors' => $errors,
            ], 422);
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            $previous = $e->getPrevious();

            $pesan = $previous instanceof \Illuminate\Database\Eloquent\ModelNotFoundException
                ? 'Data tidak ditemukan.'
                : 'Endpoint atau data tidak ditemukan.';

            return response()->json([
                'message' => $pesan,
                'data' => null,
            ], 404);
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            $status = $e->getStatusCode();
            $pesan = $e->getMessage() ?: match ($status) {
                401 => 'Tidak terautentikasi. Sertakan header Authorization: Bearer <token>.',
                403 => 'Anda tidak memiliki akses ke resource ini.',
                405 => 'Metode HTTP tidak diizinkan untuk endpoint ini.',
                409 => 'Permintaan bertentangan dengan data yang ada.',
                429 => 'Terlalu banyak permintaan. Coba lagi nanti.',
                default => 'Terjadi kesalahan pada permintaan.',
            };

            return response()->json([
                'message' => $pesan,
                'data' => null,
            ], $status);
        });
    })->create();
