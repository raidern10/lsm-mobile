<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ObservasiResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'hari_tanggal'     => $this->hari_tanggal,
            'pekerjaan_projek' => $this->pekerjaan_projek,
            'is_approved'      => (bool) $this->is_approved,
            'siswa'            => [
                'id'   => $this->user?->id,
                'name' => $this->user?->name,
                'nisn' => $this->user?->nisn,
            ],
            'guru'             => [
                'id'   => $this->guru?->id,
                'name' => $this->guru?->name,
            ],
            'items'            => $this->whenLoaded('items'),
            'created_at'       => $this->created_at,
        ];
    }
}