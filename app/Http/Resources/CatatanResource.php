<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CatatanResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                   => $this->id,
            'nama_pekerjaan'       => $this->nama_pekerjaan,
            'perencanaan_kegiatan' => $this->perencanaan_kegiatan,
            'pelaksanaan_kegiatan' => $this->pelaksanaan_kegiatan,
            'catatan_instruktur'   => $this->catatan_instruktur,
            'is_approved'          => (bool) $this->is_approved,
            'siswa'                => [
                'id'   => $this->user?->id,
                'name' => $this->user?->name,
                'nisn' => $this->user?->nisn,
            ],
            'created_at'           => $this->created_at,
        ];
    }
}