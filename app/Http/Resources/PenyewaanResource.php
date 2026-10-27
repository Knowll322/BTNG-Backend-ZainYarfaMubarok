<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PenyewaanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kode_booking' => $this->kode_booking,
            'mobil_id' => $this->mobil_id,
            'pelanggan_id' => $this->pelanggan_id,
            'tanggal_mulai' => $this->tanggal_mulai?->toDateString(),
            'tanggal_selesai' => $this->tanggal_selesai?->toDateString(),
            'tanggal_dikembalikan' => $this->tanggal_dikembalikan?->toDateString(),
            'durasi_hari' => $this->durasi_hari,
            'harga_sewa_per_hari' => $this->harga_sewa_per_hari,
            'total_biaya' => $this->total_biaya,
            'denda' => $this->denda,
            'status' => $this->status,
            'catatan' => $this->catatan,
            'mobil' => new MobilResource($this->whenLoaded('mobil')),
            'pelanggan' => new PelangganResource($this->whenLoaded('pelanggan')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
