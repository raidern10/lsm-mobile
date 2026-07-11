<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SiswaResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'nisn'       => $this->nisn,
            'status_pkl' => $this->status_pkl,
            'perusahaan' => $this->whenLoaded('perusahaan', fn () => [
                'id'   => $this->perusahaan?->id,
                'nama' => $this->perusahaan?->nama,
            ]),
        ];
    }
}