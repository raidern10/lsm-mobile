<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InformasiResource extends JsonResource
{
   public function toArray($request): array {
        return [
            'id' => $this->id, 'judul' => $this->judul, 'isi' => $this->isi,
            'created_at' => $this->created_at,
        ];
    }
}
