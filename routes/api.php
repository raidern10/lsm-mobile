<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\JurnalController;
use App\Http\Controllers\Api\AbsensiController;
use App\Http\Controllers\Api\InformasiController;
use Illuminate\Support\Facades\Route;

// Publik
Route::post('/login', [AuthController::class, 'login']);

// Butuh token
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Jurnal
    Route::get('/jurnals', [JurnalController::class, 'index']);
    Route::post('/jurnals', [JurnalController::class, 'store'])->middleware('role:siswa_pkl');
    Route::get('/jurnals/{jurnal}', [JurnalController::class, 'show']);
    Route::post('/jurnals/{jurnal}/approve', [JurnalController::class, 'approve'])
        ->middleware('role:instruktur_industri');

    // Absensi
    Route::get('/absensis', [AbsensiController::class, 'index']);
    Route::post('/absensis', [AbsensiController::class, 'store'])->middleware('role:siswa_pkl');

    // Informasi / pengumuman
    Route::get('/informasis', [InformasiController::class, 'index']);
});