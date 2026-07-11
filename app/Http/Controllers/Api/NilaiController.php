<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NilaiResource;
use App\Models\Nilai;
use App\Models\User;
use Illuminate\Http\Request;

class NilaiController extends Controller
{
    // Bobot nilai akhir (samakan dengan versi web)
    private const BOBOT_INSTRUKTUR = 0.50; // rata-rata 1–5 dikonversi ke 0–100
    private const BOBOT_GURU       = 0.20; // 0–100
    private const BOBOT_LAPORAN    = 0.30; // 0–100

    /** Hitung nilai akhir (0–100). Null jika komponen belum lengkap. */
    private function hitungNilaiAkhir(Nilai $n): ?float
    {
        if (is_null($n->rata_rata) || is_null($n->nilai_guru) || is_null($n->nilai_laporan)) {
            return null;
        }

        $instruktur100 = ($n->rata_rata / 5) * 100;

        return round(
            ($instruktur100 * self::BOBOT_INSTRUKTUR)
            + ($n->nilai_guru * self::BOBOT_GURU)
            + ($n->nilai_laporan * self::BOBOT_LAPORAN),
            2
        );
    }

    /** Daftar nilai sesuai role. */
    public function index(Request $request)
    {
        $user = $request->user();

        // Siswa: kembalikan nilainya sendiri
        if ($user->role === 'siswa_pkl') {
            $nilai = Nilai::with(['instruktur', 'guru'])->where('user_id', $user->id)->first();
            return response()->json(['data' => $nilai ? new NilaiResource($nilai) : null]);
        }

        // Guru / instruktur: daftar siswa bimbingan + nilai
        $kolomRelasi = $user->role === 'guru_pembimbing' ? 'guru_id' : 'instruktur_id';

        $siswa = User::where('role', 'siswa_pkl')
            ->where($kolomRelasi, $user->id)
            ->where('status_pkl', 'aktif')
            ->with('nilai')
            ->when($request->filled('q'), fn ($query) =>
                $query->where(fn ($u) =>
                    $u->where('name', 'like', '%' . $request->q . '%')
                      ->orWhere('nisn', 'like', '%' . $request->q . '%')))
            ->orderBy('name')
            ->paginate(15);

        return response()->json($siswa);
    }

    /** Detail nilai satu siswa. */
    public function show(Request $request, User $siswa)
    {
        $nilai = Nilai::with(['instruktur', 'guru'])->where('user_id', $siswa->id)->first();

        return response()->json(['data' => $nilai ? new NilaiResource($nilai) : null]);
    }

    /** Instruktur menyimpan komponen penilaian (soft/hard skill dll). */
    public function storeInstruktur(Request $request)
    {
        $data = $request->validate([
            'user_id'                 => 'required|exists:users,id',
            'soft_skill'              => 'required|integer|between:1,5',
            'hard_skill'              => 'required|integer|between:1,5',
            'pengembangan_hard_skill' => 'required|integer|between:1,5',
            'kewirausahaan'           => 'required|integer|between:1,5',
            'catatan_rekomendasi'     => 'nullable|string',
        ]);

        // Pastikan siswa bimbingan instruktur ini & masih aktif
        $siswa = User::where('id', $data['user_id'])
            ->where('role', 'siswa_pkl')
            ->where('instruktur_id', $request->user()->id)
            ->where('status_pkl', 'aktif')
            ->firstOrFail();

        $rataRata = ($data['soft_skill'] + $data['hard_skill']
            + $data['pengembangan_hard_skill'] + $data['kewirausahaan']) / 4;

        $nilai = Nilai::firstOrNew(['user_id' => $siswa->id]);
        $nilai->instruktur_id           = $request->user()->id;
        $nilai->soft_skill              = $data['soft_skill'];
        $nilai->hard_skill              = $data['hard_skill'];
        $nilai->pengembangan_hard_skill = $data['pengembangan_hard_skill'];
        $nilai->kewirausahaan           = $data['kewirausahaan'];
        $nilai->rata_rata               = $rataRata;
        $nilai->catatan_rekomendasi     = $data['catatan_rekomendasi'] ?? null;
        $nilai->nilai_akhir             = $this->hitungNilaiAkhir($nilai);
        $nilai->save();

        return new NilaiResource($nilai->fresh(['instruktur', 'guru']));
    }

    /** Guru menyimpan nilai guru & nilai laporan. */
    public function storeGuru(Request $request)
    {
        $data = $request->validate([
            'user_id'       => 'required|exists:users,id',
            'nilai_guru'    => 'required|numeric|between:0,100',
            'nilai_laporan' => 'required|numeric|between:0,100',
            'catatan_guru'  => 'nullable|string',
        ]);

        $siswa = User::where('id', $data['user_id'])
            ->where('role', 'siswa_pkl')
            ->where('guru_id', $request->user()->id)
            ->where('status_pkl', 'aktif')
            ->firstOrFail();

        $nilai = Nilai::firstOrNew(['user_id' => $siswa->id]);
        $nilai->guru_id       = $request->user()->id;
        $nilai->nilai_guru    = $data['nilai_guru'];
        $nilai->nilai_laporan = $data['nilai_laporan'];
        $nilai->catatan_guru  = $data['catatan_guru'] ?? null;
        $nilai->nilai_akhir   = $this->hitungNilaiAkhir($nilai);
        $nilai->save();

        return new NilaiResource($nilai->fresh(['instruktur', 'guru']));
    }
}