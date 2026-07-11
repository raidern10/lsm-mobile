<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class CetakController extends Controller
{
    // ---- Endpoint API: kembalikan signed URL (aman utk React Native) ----
    public function jurnal(Request $r, ?User $siswa = null)     { return $this->link('cetak.jurnal.file', $r, $siswa); }
    public function catatan(Request $r, ?User $siswa = null)    { return $this->link('cetak.catatan.file', $r, $siswa); }
    public function observasi(Request $r, ?User $siswa = null)  { return $this->link('cetak.observasi.file', $r, $siswa); }
    public function nilai(Request $r, ?User $siswa = null)      { return $this->link('cetak.nilai.file', $r, $siswa); }

    private function link(string $route, Request $request, ?User $siswa)
    {
        $siswa ??= $request->user(); // siswa tanpa param = dirinya sendiri
        return response()->json([
            'url' => URL::temporarySignedRoute($route, now()->addMinutes(5), ['siswa' => $siswa->id]),
        ]);
    }

    // ---- Stream file PDF (dipanggil via signed URL) ----
    public function jurnalFile(User $siswa)
    {
        $jurnals = $siswa->jurnals()->with('items')->get();
        return Pdf::loadView('pdf.jurnal', compact('siswa', 'jurnals'))
            ->stream("jurnal-{$siswa->nisn}.pdf");
    }

    public function catatanFile(User $siswa)
    {
        $catatans = $siswa->catatans()->get();
        return Pdf::loadView('pdf.catatan', compact('siswa', 'catatans'))
            ->stream("catatan-{$siswa->nisn}.pdf");
    }

    public function observasiFile(User $siswa)
    {
        $observasis = $siswa->observasis()->get();
        return Pdf::loadView('pdf.observasi', compact('siswa', 'observasis'))
            ->stream("observasi-{$siswa->nisn}.pdf");
    }

    public function nilaiFile(User $siswa)
    {
        $nilai = $siswa->nilai; // relasi hasOne/ hasMany sesuai skema
        return Pdf::loadView('pdf.nilai', compact('siswa', 'nilai'))
            ->stream("nilai-{$siswa->nisn}.pdf");
    }
}