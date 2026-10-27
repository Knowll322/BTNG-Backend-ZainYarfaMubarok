<?php

namespace Database\Factories;

use App\Models\Mobil;
use App\Models\Pelanggan;
use App\Models\Penyewaan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Penyewaan>
 */
class PenyewaanFactory extends Factory
{
    protected $model = Penyewaan::class;

    public function definition(): array
    {
        $mulai = now()->addDays($this->faker->numberBetween(1, 20));
        $selesai = $mulai->copy()->addDays($this->faker->numberBetween(1, 6));
        $durasi = (int) $mulai->copy()->startOfDay()->diffInDays($selesai->copy()->startOfDay()) + 1;

        return [
            'kode_booking' => 'BK-'.$mulai->format('Ymd').'-'.strtoupper($this->faker->unique()->bothify('#####')),
            'mobil_id' => Mobil::factory(),
            'pelanggan_id' => Pelanggan::factory(),
            'tanggal_mulai' => $mulai->toDateString(),
            'tanggal_selesai' => $selesai->toDateString(),
            'durasi_hari' => $durasi,
            'harga_sewa_per_hari' => fn (array $attrs) => Mobil::find($attrs['mobil_id'])?->harga_sewa_harian ?? 300000,
            'total_biaya' => fn (array $attrs) => $durasi * (Mobil::find($attrs['mobil_id'])?->harga_sewa_harian ?? 300000),
            'denda' => 0,
            'status' => 'booked',
            'tanggal_dikembalikan' => null,
            'catatan' => null,
        ];
    }

    public function booked(): static
    {
        return $this->state(fn () => [
            'status' => 'booked',
            'tanggal_dikembalikan' => null,
            'denda' => 0,
        ]);
    }

    public function berjalan(): static
    {
        return $this->state(fn () => [
            'tanggal_mulai' => now()->subDays(2)->toDateString(),
            'tanggal_selesai' => now()->addDays(3)->toDateString(),
            'durasi_hari' => 6,
            'status' => 'berjalan',
            'tanggal_dikembalikan' => null,
            'denda' => 0,
        ]);
    }

    public function selesai(): static
    {
        return $this->state(fn () => [
            'tanggal_mulai' => now()->subDays(10)->toDateString(),
            'tanggal_selesai' => now()->subDays(5)->toDateString(),
            'durasi_hari' => 6,
            'status' => 'selesai',
            'tanggal_dikembalikan' => now()->subDays(5)->toDateString(),
            'denda' => 0,
        ]);
    }

    public function batal(): static
    {
        return $this->state(fn () => [
            'status' => 'batal',
            'tanggal_dikembalikan' => null,
            'denda' => 0,
        ]);
    }
}
