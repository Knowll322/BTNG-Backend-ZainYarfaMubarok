<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Penyewaan extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_booking',
        'mobil_id',
        'pelanggan_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'durasi_hari',
        'harga_sewa_per_hari',
        'total_biaya',
        'denda',
        'status',
        'tanggal_dikembalikan',
        'catatan',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'tanggal_dikembalikan' => 'date',
        'durasi_hari' => 'integer',
        'harga_sewa_per_hari' => 'integer',
        'total_biaya' => 'integer',
        'denda' => 'integer',
    ];

    public function mobil(): BelongsTo
    {
        return $this->belongsTo(Mobil::class);
    }

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class);
    }

    /**
     * Penyewaan yang masih mengunci unit: booked atau berjalan.
     */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->whereIn('status', ['booked', 'berjalan']);
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['status'] ?? null, fn (Builder $q, $status) => $q->where('status', $status))
            ->when($filters['mobil_id'] ?? null, fn (Builder $q, $id) => $q->where('mobil_id', $id))
            ->when($filters['pelanggan_id'] ?? null, fn (Builder $q, $id) => $q->where('pelanggan_id', $id))
            ->when($filters['kode_booking'] ?? null, fn (Builder $q, $kode) => $q->where('kode_booking', $kode))
            ->when($filters['dari'] ?? null, fn (Builder $q, $dari) => $q->whereDate('tanggal_mulai', '>=', $dari))
            ->when($filters['sampai'] ?? null, fn (Builder $q, $sampai) => $q->whereDate('tanggal_selesai', '<=', $sampai))
            ->when($filters['search'] ?? null, function (Builder $q, string $search) {
                $q->where(function (Builder $inner) use ($search) {
                    $inner->where('kode_booking', 'like', "%{$search}%")
                        ->orWhereHas('mobil', fn (Builder $m) => $m->where('nama', 'like', "%{$search}%"))
                        ->orWhereHas('pelanggan', fn (Builder $p) => $p->where('nama', 'like', "%{$search}%"));
                });
            });
    }
}
