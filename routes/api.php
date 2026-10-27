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

// --- Mobil ---
Route::get('/mobil', [MobilController::class, 'index']);
Route::post('/mobil', [MobilController::class, 'store']);
Route::get('/mobil/{mobil}', [MobilController::class, 'show']);
Route::put('/mobil/{mobil}', [MobilController::class, 'update']);
Route::patch('/mobil/{mobil}', [MobilController::class, 'update']);
Route::delete('/mobil/{mobil}', [MobilController::class, 'destroy']);
Route::post('/mobil/{mobil}/foto', [MobilController::class, 'uploadFoto']);

// --- Pelanggan ---
Route::get('/pelanggan', [PelangganController::class, 'index']);
Route::post('/pelanggan', [PelangganController::class, 'store']);
Route::get('/pelanggan/{pelanggan}', [PelangganController::class, 'show']);
Route::put('/pelanggan/{pelanggan}', [PelangganController::class, 'update']);
Route::patch('/pelanggan/{pelanggan}', [PelangganController::class, 'update']);
Route::delete('/pelanggan/{pelanggan}', [PelangganController::class, 'destroy']);

// --- Penyewaan ---
Route::get('/penyewaan', [PenyewaanController::class, 'index']);
Route::post('/penyewaan', [PenyewaanController::class, 'store']);
Route::get('/penyewaan/{penyewaan}', [PenyewaanController::class, 'show']);
Route::put('/penyewaan/{penyewaan}', [PenyewaanController::class, 'update']);
Route::patch('/penyewaan/{penyewaan}', [PenyewaanController::class, 'update']);
Route::delete('/penyewaan/{penyewaan}', [PenyewaanController::class, 'destroy']);
Route::post('/penyewaan/{penyewaan}/kembalikan', [PenyewaanController::class, 'kembalikan']);
Route::post('/penyewaan/{penyewaan}/batalkan', [PenyewaanController::class, 'batalkan']);

// --- Statistik dashboard ---
Route::get('/statistik', [StatistikController::class, 'index']);
