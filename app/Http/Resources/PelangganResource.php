<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PelangganResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'nik' => $this->nik,
            'email' => $this->email,
            'telepon' => $this->telepon,
            'alamat' => $this->alamat,
            'nomor_sim' => $this->nomor_sim,
            'foto_ktp' => $this->foto_ktp,
            'total_penyewaan' => $this->whenCounted('penyewaans_count', fn () => (int) $this->penyewaans_count),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
