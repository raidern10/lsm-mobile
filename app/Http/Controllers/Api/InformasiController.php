<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Informasi;
use Illuminate\Http\Request;

class InformasiController extends Controller
{
    public function index(Request $request)
    {
        $informasi = Informasi::orderBy('urutan')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($informasi);
    }
}