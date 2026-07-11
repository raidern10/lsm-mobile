<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AbsensiController extends Controller
{
    public function index(Request $request)
    {
        $user  = $request->user();
        $query = Absensi::with(['siswa', 'instruktur'])->latest('tanggal');

        // Siswa hanya lihat absensinya sendiri
        if ($user->role === 'siswa_pkl') {
            $query->where('siswa_id', $user->id);
        }
        // Instruktur lihat absensi siswa binaannya
        if ($user->role === 'instruktur_industri') {
            $query->where('instruktur_id', $user->id);
        }

        // Filter opsional berdasarkan tanggal
        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $request->tanggal);
        }

        return response()->json($query->paginate(15));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tanggal'    => 'required|date',
            'status'     => ['required', Rule::in(['Hadir', 'Izin', 'Sakit', 'Alpha'])],
            'jam_masuk'  => 'nullable|date_format:H:i',
            'jam_pulang' => 'nullable|date_format:H:i',
        ]);

        $siswa = $request->user();

        $absensi = Absensi::updateOrCreate(
            [
                'siswa_id' => $siswa->id,
                'tanggal'  => $data['tanggal'],
            ],
            [
                'instruktur_id' => $siswa->instruktur_id,
                'status'        => $data['status'],
                'jam_masuk'     => $data['jam_masuk'] ?? null,
                'jam_pulang'    => $data['jam_pulang'] ?? null,
            ]
        );

        return response()->json([
            'message' => 'Absensi berhasil disimpan.',
            'absensi' => $absensi->load(['siswa', 'instruktur']),
        ], 201);
    }
}