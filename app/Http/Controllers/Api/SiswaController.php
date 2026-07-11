<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SiswaResource;
use App\Models\User;
use Illuminate\Http\Request;

class SiswaController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = User::where('role', 'siswa_pkl')
            ->with(['perusahaan', 'periodePkl']);

        // Filter sesuai role pemanggil
        match ($user->role) {
            'instruktur_industri' => $query->where('instruktur_id', $user->id),
            'guru_pembimbing'     => $query->where('guru_id', $user->id),
            default               => null, // admin: semua
        };

        return SiswaResource::collection($query->paginate(20));
    }
}