<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CatatanResource;
use App\Models\CatatanKegiatan;
use Illuminate\Http\Request;

class CatatanController extends Controller
{
    /** Daftar catatan: siswa lihat miliknya; guru/instruktur lihat binaannya. */
    public function index(Request $request)
    {
        $user  = $request->user();
        $query = CatatanKegiatan::with('user')->latest();

        if ($user->role === 'siswa_pkl') {
            $query->where('user_id', $user->id);
        } elseif ($user->role === 'guru_pembimbing') {
            $query->whereHas('user', fn ($u) => $u->where('guru_id', $user->id)->where('status_pkl', 'aktif'));
        } elseif ($user->role === 'instruktur_industri') {
            $query->whereHas('user', fn ($u) => $u->where('instruktur_id', $user->id)->where('status_pkl', 'aktif'));
        }

        if ($request->filled('status')) {
            $query->where('is_approved', $request->status === 'disetujui');
        }

        return CatatanResource::collection($query->paginate(15));
    }

    /** Siswa membuat catatan kegiatan baru. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_pekerjaan'       => 'required|string|max:255',
            'perencanaan_kegiatan' => 'required|string',
            'pelaksanaan_kegiatan' => 'required|string',
        ]);

        $catatan = CatatanKegiatan::create([
            'user_id'              => $request->user()->id,
            'nama_pekerjaan'       => $data['nama_pekerjaan'],
            'perencanaan_kegiatan' => $data['perencanaan_kegiatan'],
            'pelaksanaan_kegiatan' => $data['pelaksanaan_kegiatan'],
            'is_approved'          => false,
        ]);

        return new CatatanResource($catatan->load('user'));
    }

    /** Siswa memperbarui catatannya (selama belum disetujui). */
    public function update(Request $request, CatatanKegiatan $catatan)
    {
        abort_unless($catatan->user_id === $request->user()->id, 403, 'Akses ditolak.');
        abort_if($catatan->is_approved, 422, 'Catatan yang sudah disetujui tidak dapat diubah.');

        $data = $request->validate([
            'nama_pekerjaan'       => 'required|string|max:255',
            'perencanaan_kegiatan' => 'required|string',
            'pelaksanaan_kegiatan' => 'required|string',
        ]);

        $catatan->update($data);

        return new CatatanResource($catatan->fresh('user'));
    }

    /** Siswa menghapus catatannya (selama belum disetujui). */
    public function destroy(Request $request, CatatanKegiatan $catatan)
    {
        abort_unless($catatan->user_id === $request->user()->id, 403, 'Akses ditolak.');
        abort_if($catatan->is_approved, 422, 'Catatan yang sudah disetujui tidak dapat dihapus.');

        $catatan->delete();

        return response()->json(['message' => 'Catatan kegiatan berhasil dihapus.']);
    }

    /** Instruktur menyetujui / membatalkan persetujuan catatan. */
    public function approve(Request $request, CatatanKegiatan $catatan)
    {
        abort_unless($catatan->user->instruktur_id === $request->user()->id, 403, 'Akses ditolak.');

        $data = $request->validate([
            'is_approved' => 'required|boolean',
        ]);

        $catatan->update(['is_approved' => $data['is_approved']]);

        return new CatatanResource($catatan->fresh('user'));
    }

    /** Instruktur mengisi / memperbarui catatan instruktur. */
    public function komentar(Request $request, CatatanKegiatan $catatan)
    {
        abort_unless($catatan->user->instruktur_id === $request->user()->id, 403, 'Akses ditolak.');

        $data = $request->validate([
            'catatan_instruktur' => 'required|string',
        ]);

        $catatan->update(['catatan_instruktur' => $data['catatan_instruktur']]);

        return new CatatanResource($catatan->fresh('user'));
    }
}