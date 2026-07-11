<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ObservasiResource;
use App\Models\Observasi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ObservasiController extends Controller
{
    /** Daftar observasi sesuai role. */
    public function index(Request $request)
    {
        $user  = $request->user();
        $query = Observasi::with(['user', 'guru', 'items'])->latest();

        if ($user->role === 'guru_pembimbing') {
            $query->where('guru_id', $user->id);
        } elseif ($user->role === 'siswa_pkl') {
            $query->where('user_id', $user->id);
        } elseif ($user->role === 'instruktur_industri') {
            $query->whereHas('user', fn ($u) => $u->where('instruktur_id', $user->id)->where('status_pkl', 'aktif'));
        }

        if ($request->filled('status')) {
            $query->where('is_approved', $request->status === 'disetujui');
        }

        return ObservasiResource::collection($query->paginate(15));
    }

    public function show(Request $request, Observasi $observasi)
    {
        return new ObservasiResource($observasi->load(['user', 'guru', 'items']));
    }

    /** Guru membuat observasi baru (1 observasi = banyak poin permasalahan & solusi). */
    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id'              => 'required|exists:users,id',
            'hari_tanggal'         => 'required|date',
            'pekerjaan_projek'     => 'nullable|string|max:255',
            'items'                => 'required|array|min:1',
            'items.*.permasalahan' => 'required|string',
            'items.*.solusi'       => 'required|string',
        ]);

        // Pastikan siswa memang bimbingan guru ini
        $siswa = User::where('id', $data['user_id'])
            ->where('guru_id', $request->user()->id)
            ->firstOrFail();

        $observasi = DB::transaction(function () use ($data, $siswa, $request) {
            $observasi = Observasi::create([
                'user_id'          => $siswa->id,
                'guru_id'          => $request->user()->id,
                'hari_tanggal'     => $data['hari_tanggal'],
                'pekerjaan_projek' => $data['pekerjaan_projek'] ?? null,
                'is_approved'      => false,
            ]);

            $observasi->items()->createMany($data['items']);

            return $observasi;
        });

        return new ObservasiResource($observasi->load(['user', 'guru', 'items']));
    }

    /** Guru memperbarui observasi (poin lama diganti seluruhnya). */
    public function update(Request $request, Observasi $observasi)
    {
        abort_unless($observasi->guru_id === $request->user()->id, 403, 'Akses ditolak.');

        $data = $request->validate([
            'user_id'              => 'required|exists:users,id',
            'hari_tanggal'         => 'required|date',
            'pekerjaan_projek'     => 'nullable|string|max:255',
            'items'                => 'required|array|min:1',
            'items.*.permasalahan' => 'required|string',
            'items.*.solusi'       => 'required|string',
        ]);

        $siswa = User::where('id', $data['user_id'])
            ->where('guru_id', $request->user()->id)
            ->firstOrFail();

        DB::transaction(function () use ($observasi, $data, $siswa) {
            $observasi->update([
                'user_id'          => $siswa->id,
                'hari_tanggal'     => $data['hari_tanggal'],
                'pekerjaan_projek' => $data['pekerjaan_projek'] ?? null,
            ]);

            $observasi->items()->delete();
            $observasi->items()->createMany($data['items']);
        });

        return new ObservasiResource($observasi->fresh(['user', 'guru', 'items']));
    }

    /** Guru menghapus observasi beserta poinnya. */
    public function destroy(Request $request, Observasi $observasi)
    {
        abort_unless($observasi->guru_id === $request->user()->id, 403, 'Akses ditolak.');

        $observasi->delete();

        return response()->json(['message' => 'Data observasi berhasil dihapus.']);
    }

    /** Instruktur menyetujui / membatalkan persetujuan observasi. */
    public function approve(Request $request, Observasi $observasi)
    {
        abort_unless($observasi->user->instruktur_id === $request->user()->id, 403, 'Akses ditolak.');

        $data = $request->validate([
            'is_approved' => 'required|boolean',
        ]);

        $observasi->update(['is_approved' => $data['is_approved']]);

        return new ObservasiResource($observasi->fresh(['user', 'guru', 'items']));
    }
}