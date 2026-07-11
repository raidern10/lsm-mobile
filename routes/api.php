<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\JurnalController;
use App\Http\Controllers\Api\AbsensiController;
use App\Http\Controllers\Api\InformasiController;
use App\Http\Controllers\Api\CatatanController;
use App\Http\Controllers\Api\ObservasiController;
use App\Http\Controllers\Api\NilaiController;
use App\Http\Controllers\Api\CetakController;
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

    // Catatan kegiatan
    Route::get('/catatans', [CatatanController::class, 'index']);
    Route::post('/catatans', [CatatanController::class, 'store'])->middleware('role:siswa_pkl');
    Route::put('/catatans/{catatan}', [CatatanController::class, 'update'])->middleware('role:siswa_pkl');
    Route::delete('/catatans/{catatan}', [CatatanController::class, 'destroy'])->middleware('role:siswa_pkl');
    Route::post('/catatans/{catatan}/approve', [CatatanController::class, 'approve'])->middleware('role:instruktur_industri');
    Route::post('/catatans/{catatan}/komentar', [CatatanController::class, 'komentar'])->middleware('role:instruktur_industri');

    // Observasi
    Route::get('/observasis', [ObservasiController::class, 'index']);
    Route::get('/observasis/{observasi}', [ObservasiController::class, 'show']);
    Route::post('/observasis', [ObservasiController::class, 'store'])->middleware('role:guru_pembimbing');
    Route::put('/observasis/{observasi}', [ObservasiController::class, 'update'])->middleware('role:guru_pembimbing');
    Route::delete('/observasis/{observasi}', [ObservasiController::class, 'destroy'])->middleware('role:guru_pembimbing');
    Route::post('/observasis/{observasi}/approve', [ObservasiController::class, 'approve'])->middleware('role:instruktur_industri');

    // Penilaian
    Route::get('/nilais', [NilaiController::class, 'index']);
    Route::get('/nilais/{siswa}', [NilaiController::class, 'show']);
    Route::post('/nilais/instruktur', [NilaiController::class, 'storeInstruktur'])->middleware('role:instruktur_industri');
    Route::post('/nilais/guru', [NilaiController::class, 'storeGuru'])->middleware('role:guru_pembimbing');

    // Cetak PDF (mengembalikan URL unduhan bertanda tangan)
    Route::get('/cetak/jurnal/{siswa?}', [CetakController::class, 'jurnal']);
    Route::get('/cetak/catatan/{siswa?}', [CetakController::class, 'catatan']);
    Route::get('/cetak/observasi/{siswa?}', [CetakController::class, 'observasi']);
    Route::get('/cetak/nilai/{siswa?}', [CetakController::class, 'nilai']);
});