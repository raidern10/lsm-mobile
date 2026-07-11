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
| Penamaan path dibuat spesifik per role agar konsisten & self-documenting.
*/

// ============================================================
// PUBLIK
// ============================================================
Route::post('/login', [AuthController::class, 'login']);

// ============================================================
// BUTUH TOKEN (auth:sanctum)
// ============================================================
Route::middleware('auth:sanctum')->group(function () {

    // ---- AKUN (semua role) ----
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // ---- GLOBAL: INFORMASI & DOKUMEN (semua role) ----
    Route::get('/informasis', [InformasiController::class, 'index']);
    Route::get('/dokumen/surat-tugas/lihat',     [DokumenController::class, 'suratTugasLihat']);
    Route::get('/dokumen/surat-tugas/download',  [DokumenController::class, 'suratTugasDownload']);
    Route::get('/dokumen/{siswa}/{jenis}/lihat', [DokumenController::class, 'lihatSiswa'])
        ->whereNumber('siswa');

    // ---- SHARED: JURNAL (siswa lihat/tambah miliknya, instruktur lihat/approve bimbingannya) ----
    Route::get('/jurnals', [JurnalController::class, 'index']);            // difilter per role di controller
    Route::get('/jurnals/{jurnal}', [JurnalController::class, 'show']);
    Route::post('/jurnals', [JurnalController::class, 'store'])
        ->middleware('role:siswa_pkl');
    Route::post('/jurnals/{jurnal}/approve', [JurnalController::class, 'approve'])
        ->middleware('role:instruktur_industri');

    // ---- SHARED: ABSENSI ----
    Route::get('/absensis', [AbsensiController::class, 'index']);          // difilter per role di controller
    Route::post('/absensis', [AbsensiController::class, 'store'])
        ->middleware('role:siswa_pkl');

    // ============================================================
    // ROLE: SISWA PKL
    // ============================================================
    Route::middleware('role:siswa_pkl')->prefix('siswa')->group(function () {
        // Catatan kegiatan
        Route::get('/catatan',        [CatatanController::class, 'index']);
        Route::post('/catatan',       [CatatanController::class, 'store']);
        Route::match(['put', 'patch'], '/catatan/{catatan}', [CatatanController::class, 'update']);
        Route::delete('/catatan/{catatan}', [CatatanController::class, 'destroy']);

        // Observasi & Nilai (lihat milik sendiri)
        Route::get('/observasi', [ObservasiController::class, 'index']);
        Route::get('/nilai',     [NilaiController::class, 'index']);

        // Upload dokumen pendukung
        Route::post('/dokumen', [DokumenController::class, 'uploadSiswa']);
    });

    // ============================================================
    // ROLE: INSTRUKTUR INDUSTRI
    // ============================================================
    Route::middleware('role:instruktur_industri')->prefix('instruktur')->group(function () {
        Route::get('/siswa',                  [InstrukturController::class, 'siswa']);
        Route::put('/jurnal/{jurnal}/update', [InstrukturController::class, 'jurnalUpdate']);

        // Absensi
        Route::get('/absensi',  [InstrukturController::class, 'absensiIndex']);
        Route::post('/absensi', [InstrukturController::class, 'absensiStore']);

        // Catatan (validasi & feedback)
        // Daftar catatan siswa binaan (controller sudah memfilter per role)
Route::get('/catatan', [CatatanController::class, 'index']);
        Route::put('/catatan/{catatan}/approve',  [CatatanController::class, 'approve']);
        Route::put('/catatan/{catatan}/batal',    [InstrukturController::class, 'catatanBatal']);
        Route::put('/catatan/{catatan}/komentar', [CatatanController::class, 'komentar']);
        

        // Observasi (validasi)
        Route::put('/observasi/{observasi}/approve', [ObservasiController::class, 'approve']);
        Route::put('/observasi/{observasi}/batal',   [InstrukturController::class, 'observasiBatal']);

        // Nilai
        Route::get('/nilai',  [NilaiController::class, 'index']);
        Route::post('/nilai', [NilaiController::class, 'storeInstruktur']);
    });

    // ============================================================
    // ROLE: GURU PEMBIMBING
    // ============================================================
    Route::middleware('role:guru_pembimbing')->prefix('guru')->group(function () {
        Route::get('/dashboard',          [GuruController::class, 'dashboard']);
        Route::get('/siswa',              [GuruController::class, 'siswa']);
        Route::get('/monitoring/jurnal',  [GuruController::class, 'monitoringJurnal']);
        Route::get('/monitoring/absensi', [GuruController::class, 'monitoringAbsensi']);
        Route::get('/catatan',            [GuruController::class, 'catatan']);

        // Kelola lembar observasi (CRUD)
        Route::get('/observasi',                [ObservasiController::class, 'index']);
        Route::post('/observasi',               [ObservasiController::class, 'store']);
        Route::put('/observasi/{observasi}',    [ObservasiController::class, 'update']);
        Route::delete('/observasi/{observasi}', [ObservasiController::class, 'destroy']);

        // Nilai (akademis)
        Route::get('/nilai',  [NilaiController::class, 'index']);
        Route::post('/nilai', [NilaiController::class, 'storeGuru']);
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