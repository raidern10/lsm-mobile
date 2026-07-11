<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class JurnalResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                 => $this->id,
            'hari_tanggal'       => $this->hari_tanggal,
            'status_persetujuan' => $this->status_persetujuan,
            'catatan_instruktur' => $this->catatan_instruktur,
            'siswa'              => [
                'id'   => $this->siswa?->id,
                'name' => $this->siswa?->name,
                'nisn' => $this->siswa?->nisn,
            ],
            'items'      => $this->whenLoaded('items'),
            'created_at' => $this->created_at,
        ];
    }
}