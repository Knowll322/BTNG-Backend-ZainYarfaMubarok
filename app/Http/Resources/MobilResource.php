<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MobilResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'plat_nomor' => $this->plat_nomor,
            'nama' => $this->nama,
            'brand' => $this->brand,
            'harga' => $this->harga,
            'harga_sewa_harian' => $this->harga_sewa_harian,
            'tahun' => $this->tahun,
            'transmisi' => $this->transmisi,
            'bahan_bakar' => $this->bahan_bakar,
            'kapasitas_kursi' => $this->kapasitas_kursi,
            'status' => $this->status,
            'foto' => $this->foto,
            'deskripsi' => $this->deskripsi,
            'penyewaan_aktif' => $this->whenCounted('penyewaans_aktif_count', fn () => (int) $this->penyewaans_aktif_count),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
