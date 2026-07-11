<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\JurnalController;
use App\Http\Controllers\Api\AbsensiController;
use App\Http\Controllers\Api\InformasiController;
use App\Http\Controllers\Api\CatatanController;
use App\Http\Controllers\Api\ObservasiController;
use App\Http\Controllers\Api\NilaiController;
use App\Http\Controllers\Api\CetakController;
use App\Http\Controllers\Api\DokumenController;
use App\Http\Controllers\Api\GuruController;
use App\Http\Controllers\Api\InstrukturController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Sistem Monitoring PKL (Mobile / React Native)
|--------------------------------------------------------------------------
| Semua endpoint (kecuali /login) dikunci auth:sanctum.
| Hak akses per-role divalidasi middleware role:<nama_role>.
*/

// ============================================================
// PUBLIK
// ============================================================
Route::post('/login', [AuthController::class, 'login']);

// ============================================================
// BUTUH TOKEN (auth:sanctum)
// ============================================================
Route::middleware('auth:sanctum')->group(function () {

    // ---- AKUN ----
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // ---- INFORMASI / PENGUMUMAN (semua role) ----
    Route::get('/informasis', [InformasiController::class, 'index']);

    // ---- DOKUMEN GLOBAL & PER-SISWA ----
    Route::get('/dokumen/surat-tugas/lihat',     [DokumenController::class, 'suratTugasLihat']);
    Route::get('/dokumen/surat-tugas/download',  [DokumenController::class, 'suratTugasDownload']);
    Route::get('/dokumen/{siswa}/{jenis}/lihat', [DokumenController::class, 'lihatSiswa'])
        ->whereNumber('siswa');

    // ============================================================
    // JURNAL HARIAN
    // ============================================================
    Route::get('/jurnals', [JurnalController::class, 'index']);
    Route::get('/jurnals/{jurnal}', [JurnalController::class, 'show']);
    Route::post('/jurnals', [JurnalController::class, 'store'])
        ->middleware('role:siswa_pkl');
    Route::post('/jurnals/{jurnal}/approve', [JurnalController::class, 'approve'])
        ->middleware('role:instruktur_industri');

    // ============================================================
    // ABSENSI
    // ============================================================
    Route::get('/absensis', [AbsensiController::class, 'index']);
    Route::post('/absensis', [AbsensiController::class, 'store'])
        ->middleware('role:siswa_pkl');

    // ============================================================
    // CATATAN KEGIATAN
    // ============================================================
    Route::get('/catatans', [CatatanController::class, 'index']);
    Route::post('/catatans', [CatatanController::class, 'store'])
        ->middleware('role:siswa_pkl');
    Route::put('/catatans/{catatan}', [CatatanController::class, 'update'])
        ->middleware('role:siswa_pkl');
    Route::delete('/catatans/{catatan}', [CatatanController::class, 'destroy'])
        ->middleware('role:siswa_pkl');
    Route::post('/catatans/{catatan}/approve', [CatatanController::class, 'approve'])
        ->middleware('role:instruktur_industri');
    Route::post('/catatans/{catatan}/komentar', [CatatanController::class, 'komentar'])
        ->middleware('role:instruktur_industri');

    // ============================================================
    // OBSERVASI
    // ============================================================
    Route::get('/observasis', [ObservasiController::class, 'index']);
    Route::get('/observasis/{observasi}', [ObservasiController::class, 'show']);
    Route::post('/observasis', [ObservasiController::class, 'store'])
        ->middleware('role:guru_pembimbing');
    Route::put('/observasis/{observasi}', [ObservasiController::class, 'update'])
        ->middleware('role:guru_pembimbing');
    Route::delete('/observasis/{observasi}', [ObservasiController::class, 'destroy'])
        ->middleware('role:guru_pembimbing');
    Route::post('/observasis/{observasi}/approve', [ObservasiController::class, 'approve'])
        ->middleware('role:instruktur_industri');

    // ============================================================
    // PENILAIAN
    // ============================================================
    Route::get('/nilais', [NilaiController::class, 'index']);
    Route::get('/nilais/{siswa}', [NilaiController::class, 'show'])->whereNumber('siswa');
    Route::post('/nilais/instruktur', [NilaiController::class, 'storeInstruktur'])
        ->middleware('role:instruktur_industri');
    Route::post('/nilais/guru', [NilaiController::class, 'storeGuru'])
        ->middleware('role:guru_pembimbing');

    // ============================================================
    // SISWA PKL — upload dokumen pendukung
    // ============================================================
    Route::middleware('role:siswa_pkl')->prefix('siswa')->group(function () {
        Route::post('/dokumen', [DokumenController::class, 'uploadSiswa']);
    });

    // ============================================================
    // INSTRUKTUR INDUSTRI
    // ============================================================
    Route::middleware('role:instruktur_industri')->prefix('instruktur')->group(function () {
        Route::get('/siswa',                       [InstrukturController::class, 'siswa']);
        Route::put('/jurnal/{jurnal}/update',      [InstrukturController::class, 'jurnalUpdate']);
        Route::get('/absensi',                     [InstrukturController::class, 'absensiIndex']);
        Route::post('/absensi',                    [InstrukturController::class, 'absensiStore']);
        Route::put('/catatan/{catatan}/batal',     [InstrukturController::class, 'catatanBatal']);
        Route::put('/observasi/{observasi}/batal', [InstrukturController::class, 'observasiBatal']);
    });

    // ============================================================
    // GURU PEMBIMBING
    // ============================================================
    Route::middleware('role:guru_pembimbing')->prefix('guru')->group(function () {
        Route::get('/dashboard',          [GuruController::class, 'dashboard']);
        Route::get('/siswa',              [GuruController::class, 'siswa']);
        Route::get('/monitoring/jurnal',  [GuruController::class, 'monitoringJurnal']);
        Route::get('/monitoring/absensi', [GuruController::class, 'monitoringAbsensi']);
        Route::get('/catatan',            [GuruController::class, 'catatan']);
    });

    // ============================================================
    // CETAK PDF (mengembalikan signed download URL)
    // siswa: tanpa param (otomatis dirinya). guru/instruktur/admin: sertakan id siswa.
    // ============================================================
    Route::get('/cetak/jurnal/{siswa?}',    [CetakController::class, 'jurnal'])->whereNumber('siswa');
    Route::get('/cetak/catatan/{siswa?}',   [CetakController::class, 'catatan'])->whereNumber('siswa');
    Route::get('/cetak/observasi/{siswa?}', [CetakController::class, 'observasi'])->whereNumber('siswa');
    Route::get('/cetak/nilai/{siswa?}',     [CetakController::class, 'nilai'])->whereNumber('siswa');
});