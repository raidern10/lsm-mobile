<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AbsensiResource;
use App\Http\Resources\JurnalResource;
use App\Http\Resources\SiswaResource;
use App\Models\Absensi;
use App\Models\Catatan;
use App\Models\Jurnal;
use App\Models\Observasi;
use App\Models\User;
use Illuminate\Http\Request;

class InstrukturController extends Controller
{
    // GET /api/instruktur/siswa
    public function siswa(Request $request)
    {
        $siswa = User::where('role', 'siswa_pkl')
            ->where('instruktur_id', $request->user()->id)
            ->with('perusahaan')->paginate(20);

        return SiswaResource::collection($siswa);
    }

    // PUT /api/instruktur/jurnal/{jurnal}/update
    public function jurnalUpdate(Request $request, Jurnal $jurnal)
    {
        $data = $request->validate([
            'status_persetujuan' => 'required|in:pending,disetujui,revisi',
            'catatan_instruktur' => 'nullable|string',
        ]);
        $jurnal->update($data + ['disetujui_oleh' => $request->user()->id]);

        return new JurnalResource($jurnal->fresh(['siswa', 'items']));
    }

    // GET /api/instruktur/absensi
    public function absensiIndex(Request $request)
    {
        $absensi = Absensi::whereHas('siswa', fn ($q) =>
                $q->where('instruktur_id', $request->user()->id))
            ->with('siswa')->latest()->paginate(20);

        return AbsensiResource::collection($absensi);
    }

    // POST /api/instruktur/absensi
    public function absensiStore(Request $request)
    {
        $data = $request->validate([
            'siswa_id' => 'required|exists:users,id',
            'tanggal'  => 'required|date',
            'status'   => 'required|in:hadir,izin,sakit,alfa',
        ]);
        $absensi = Absensi::create($data);

        return new AbsensiResource($absensi->load('siswa'));
    }

    // PUT /api/instruktur/catatan/{catatan}/batal
    public function catatanBatal(Catatan $catatan)
    {
        $catatan->update(['status' => 'pending', 'disetujui_oleh' => null]);
        return response()->json(['message' => 'Persetujuan catatan dibatalkan.']);
    }

    // PUT /api/instruktur/observasi/{observasi}/batal
    public function observasiBatal(Observasi $observasi)
    {
        $observasi->update(['status' => 'pending', 'disetujui_oleh' => null]);
        return response()->json(['message' => 'Persetujuan observasi dibatalkan.']);
    }
}