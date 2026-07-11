<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AbsensiResource extends JsonResource
{
   public function toArray($request): array {
        return [
            'id' => $this->id, 'tanggal' => $this->tanggal, 'status' => $this->status,
            'jam_masuk' => $this->jam_masuk, 'jam_pulang' => $this->jam_pulang,
            'siswa' => ['id' => $this->siswa?->id, 'name' => $this->siswa?->name],
        ];
    }
}
