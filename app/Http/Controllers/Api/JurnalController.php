<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\JurnalResource;
use App\Models\Jurnal;
use Illuminate\Http\Request;

class JurnalController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Jurnal::with(['siswa', 'items'])->latest();

        // Siswa hanya lihat jurnalnya sendiri
        if ($user->role === 'siswa_pkl') {
            $query->where('siswa_id', $user->id);
        }
        // Instruktur lihat jurnal siswa binaannya
        if ($user->role === 'instruktur_industri') {
            $query->whereHas('siswa', fn ($q) => $q->where('instruktur_id', $user->id));
        }

        return JurnalResource::collection($query->paginate(15));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'hari_tanggal'        => 'required|date',
            'items'               => 'required|array|min:1',
            'items.*.unit_kerja'  => 'required|string',
            'items.*.uraian'      => 'required|string',
        ]);

        $jurnal = Jurnal::create([
            'siswa_id'           => $request->user()->id,
            'hari_tanggal'       => $data['hari_tanggal'],
            'status_persetujuan' => 'pending',
        ]);

        $jurnal->items()->createMany($data['items']);

        return new JurnalResource($jurnal->load(['siswa', 'items']));
    }

    public function show(Jurnal $jurnal)
    {
        return new JurnalResource($jurnal->load(['siswa', 'items']));
    }

    public function approve(Request $request, Jurnal $jurnal)
    {
        $jurnal->update([
            'status_persetujuan' => $request->input('status', 'disetujui'),
            'catatan_instruktur' => $request->input('catatan'),
            'disetujui_oleh'     => $request->user()->id,
        ]);

        return new JurnalResource($jurnal->fresh(['siswa', 'items']));
    }
}