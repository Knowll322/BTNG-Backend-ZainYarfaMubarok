<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MobilController;
use App\Http\Controllers\Api\PelangganController;
use App\Http\Controllers\Api\PenyewaanController;
use App\Http\Controllers\Api\StatistikController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Showroom & Rental Mobil
|--------------------------------------------------------------------------
| Semua respons memakai amplop { message, data } melalui ApiResponse.
| Dokumentasi interaktif tersedia di /docs/api (Scramble).
*/

Route::get('/health', fn () => response()->json([
    'message' => 'API Showroom & Rental Mobil berjalan',
    'data' => [
        'status' => 'ok',
        'versi' => '1.0.0',
        'waktu' => now()->toIso8601String(),
    ],
]));

// --- Autentikasi (bonus Sanctum) ---
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

/*
 * Rute baca dibuka untuk umum agar penguji dapat langsung mencoba GET
 * dari /docs/api tanpa perlu membuat akun lebih dulu.
 */
Route::get('/mobil', [MobilController::class, 'index']);
Route::get('/mobil/{mobil}', [MobilController::class, 'show']);

Route::get('/pelanggan', [PelangganController::class, 'index']);
Route::get('/pelanggan/{pelanggan}', [PelangganController::class, 'show']);

Route::get('/penyewaan', [PenyewaanController::class, 'index']);
Route::get('/penyewaan/{penyewaan}', [PenyewaanController::class, 'show']);

/*
 * Rute tulis wajib token Sanctum.
 *
 * Tanpa ini siapa pun yang mengetahui URL dapat menambah, mengubah, dan
 * menghapus seluruh isi basis data produksi. Token diperoleh dari
 * POST /api/auth/login, lalu dikirim sebagai header
 * Authorization: Bearer <token>.
 */
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/mobil', [MobilController::class, 'store']);
    Route::put('/mobil/{mobil}', [MobilController::class, 'update']);
    Route::patch('/mobil/{mobil}', [MobilController::class, 'update']);
    Route::delete('/mobil/{mobil}', [MobilController::class, 'destroy']);
    Route::post('/mobil/{mobil}/foto', [MobilController::class, 'uploadFoto']);

    Route::post('/pelanggan', [PelangganController::class, 'store']);
    Route::put('/pelanggan/{pelanggan}', [PelangganController::class, 'update']);
    Route::patch('/pelanggan/{pelanggan}', [PelangganController::class, 'update']);
    Route::delete('/pelanggan/{pelanggan}', [PelangganController::class, 'destroy']);

    Route::post('/penyewaan', [PenyewaanController::class, 'store']);
    Route::put('/penyewaan/{penyewaan}', [PenyewaanController::class, 'update']);
    Route::patch('/penyewaan/{penyewaan}', [PenyewaanController::class, 'update']);
    Route::delete('/penyewaan/{penyewaan}', [PenyewaanController::class, 'destroy']);
    Route::post('/penyewaan/{penyewaan}/kembalikan', [PenyewaanController::class, 'kembalikan']);
    Route::post('/penyewaan/{penyewaan}/batalkan', [PenyewaanController::class, 'batalkan']);
});

// --- Statistik dashboard ---
Route::get('/statistik', [StatistikController::class, 'index']);
