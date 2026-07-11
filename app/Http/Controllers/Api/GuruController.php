<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AbsensiResource;
use App\Http\Resources\CatatanResource;
use App\Http\Resources\JurnalResource;
use App\Http\Resources\SiswaResource;
use App\Models\Absensi;
use App\Models\Catatan;
use App\Models\Jurnal;
use App\Models\User;
use Illuminate\Http\Request;

class GuruController extends Controller
{
    // GET /api/guru/dashboard
    public function dashboard(Request $request)
    {
        $guruId = $request->user()->id;
        $base   = User::where('role', 'siswa_pkl')->where('guru_id', $guruId);

        return response()->json([
            'total_bimbingan' => (clone $base)->count(),
            'siswa_aktif'     => (clone $base)->where('status_pkl', 'aktif')->count(),
            'belum_pkl'       => (clone $base)->where('status_pkl', 'belum')->count(),
            'selesai_pkl'     => (clone $base)->where('status_pkl', 'selesai')->count(),
        ]);
    }

    // GET /api/guru/siswa
    public function siswa(Request $request)
    {
        $siswa = User::where('role', 'siswa_pkl')
            ->where('guru_id', $request->user()->id)
            ->with(['perusahaan', 'periodePkl'])
            ->paginate(20);

        return SiswaResource::collection($siswa);
    }

    // GET /api/guru/monitoring/jurnal
    public function monitoringJurnal(Request $request)
    {
        $jurnals = Jurnal::whereHas('siswa', fn ($q) =>
                $q->where('guru_id', $request->user()->id))
            ->with(['siswa', 'items'])->latest()->paginate(15);

        return JurnalResource::collection($jurnals);
    }

    // GET /api/guru/monitoring/absensi
    public function monitoringAbsensi(Request $request)
    {
        $absensi = Absensi::whereHas('siswa', fn ($q) =>
                $q->where('guru_id', $request->user()->id))
            ->with('siswa')->latest()->paginate(20);

        return AbsensiResource::collection($absensi);
    }

    // GET /api/guru/catatan
    public function catatan(Request $request)
    {
        $catatan = Catatan::whereHas('siswa', fn ($q) =>
                $q->where('guru_id', $request->user()->id))
            ->with('siswa')->latest()->paginate(20);

        return CatatanResource::collection($catatan);
    }
}