<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mobil extends Model
{
    use HasFactory;

    protected $fillable = [
        'plat_nomor',
        'nama',
        'brand',
        'harga',
        'harga_sewa_harian',
        'tahun',
        'transmisi',
        'bahan_bakar',
        'kapasitas_kursi',
        'status',
        'foto',
        'deskripsi',
    ];

    protected $attributes = [
        'status' => 'tersedia',
    ];

    protected $casts = [
        'harga' => 'integer',
        'harga_sewa_harian' => 'integer',
        'tahun' => 'integer',
        'kapasitas_kursi' => 'integer',
    ];

    public function penyewaans(): HasMany
    {
        return $this->hasMany(Penyewaan::class);
    }

    public function scopeTersedia(Builder $query): Builder
    {
        return $query->where('status', 'tersedia');
    }

    /**
     * Filter daftar mobil dari query string.
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, function (Builder $q, string $search) {
                $q->where(function (Builder $inner) use ($search) {
                    $inner->where('nama', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%")
                        ->orWhere('plat_nomor', 'like', "%{$search}%");
                });
            })
            ->when($filters['brand'] ?? null, fn (Builder $q, $brand) => $q->where('brand', $brand))
            ->when($filters['status'] ?? null, fn (Builder $q, $status) => $q->where('status', $status))
            ->when($filters['transmisi'] ?? null, fn (Builder $q, $transmisi) => $q->where('transmisi', $transmisi))
            ->when($filters['bahan_bakar'] ?? null, fn (Builder $q, $bahan) => $q->where('bahan_bakar', $bahan))
            ->when($filters['tahun'] ?? null, fn (Builder $q, $tahun) => $q->where('tahun', $tahun))
            ->when($filters['min_harga'] ?? null, fn (Builder $q, $min) => $q->where('harga', '>=', (int) $min))
            ->when($filters['max_harga'] ?? null, fn (Builder $q, $max) => $q->where('harga', '<=', (int) $max))
            ->when($filters['max_harga_sewa'] ?? null, fn (Builder $q, $max) => $q->where('harga_sewa_harian', '<=', (int) $max));
    }
}
