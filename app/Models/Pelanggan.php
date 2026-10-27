<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pelanggan extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'nik',
        'email',
        'telepon',
        'alamat',
        'nomor_sim',
        'foto_ktp',
    ];

    public function penyewaans(): HasMany
    {
        return $this->hasMany(Penyewaan::class);
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, function (Builder $q, string $search) {
                $q->where(function (Builder $inner) use ($search) {
                    $inner->where('nama', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('nik', 'like', "%{$search}%")
                        ->orWhere('telepon', 'like', "%{$search}%");
                });
            })
            ->when($filters['email'] ?? null, fn (Builder $q, $email) => $q->where('email', $email));
    }
}
