<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dokumen;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DokumenController extends Controller
{
    // GET /api/dokumen/surat-tugas/lihat
    public function suratTugasLihat()
    {
        $dok = Dokumen::where('jenis', 'surat_tugas')->latest()->firstOrFail();
        return response()->json(['url' => Storage::url($dok->path)]);
    }

    // GET /api/dokumen/surat-tugas/download
    public function suratTugasDownload()
    {
        $dok = Dokumen::where('jenis', 'surat_tugas')->latest()->firstOrFail();
        return Storage::download($dok->path);
    }

    // GET /api/dokumen/{siswa}/{jenis}/lihat  (surat_penerimaan / laporan_akhir)
    public function lihatSiswa(User $siswa, string $jenis)
    {
        $dok = Dokumen::where('siswa_id', $siswa->id)->where('jenis', $jenis)->firstOrFail();
        return response()->json(['url' => Storage::url($dok->path)]);
    }

    // POST /api/siswa/dokumen  (upload oleh siswa)
    public function uploadSiswa(Request $request)
    {
        $data = $request->validate([
            'jenis' => 'required|in:surat_penerimaan,laporan_akhir',
            'file'  => 'required|file|mimes:pdf|max:5120',
        ]);
        $path = $request->file('file')->store('dokumen', 'public');

        $dok = Dokumen::updateOrCreate(
            ['siswa_id' => $request->user()->id, 'jenis' => $data['jenis']],
            ['path' => $path]
        );

        return response()->json(['message' => 'Dokumen terunggah.', 'url' => Storage::url($dok->path)], 201);
    }
}