<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NilaiResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                      => $this->id,
            'soft_skill'              => $this->soft_skill,
            'hard_skill'              => $this->hard_skill,
            'pengembangan_hard_skill' => $this->pengembangan_hard_skill,
            'kewirausahaan'           => $this->kewirausahaan,
            'rata_rata'               => $this->rata_rata,
            'catatan_rekomendasi'     => $this->catatan_rekomendasi,
            'nilai_guru'              => $this->nilai_guru,
            'nilai_laporan'           => $this->nilai_laporan,
            'catatan_guru'            => $this->catatan_guru,
            'nilai_akhir'             => $this->nilai_akhir,
            'instruktur'              => [
                'id'   => $this->instruktur?->id,
                'name' => $this->instruktur?->name,
            ],
            'guru'                    => [
                'id'   => $this->guru?->id,
                'name' => $this->guru?->name,
            ],
            'updated_at'              => $this->updated_at,
        ];
    }
}