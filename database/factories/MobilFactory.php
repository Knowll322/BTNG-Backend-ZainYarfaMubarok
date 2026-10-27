<?php

namespace Database\Factories;

use App\Models\Mobil;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mobil>
 */
class MobilFactory extends Factory
{
    protected $model = Mobil::class;

    /**
     * Daftar model beserta brand dan rentang harga yang wajar.
     */
    public const MODEL_MOBIL = [
        ['nama' => 'Toyota Avanza 1.5 G CVT', 'brand' => 'Toyota', 'harga' => 275000000, 'sewa' => 350000, 'kursi' => 7],
        ['nama' => 'Honda Brio Satya E', 'brand' => 'Honda', 'harga' => 175000000, 'sewa' => 275000, 'kursi' => 5],
        ['nama' => 'Suzuki Ertiga GX AT', 'brand' => 'Suzuki', 'harga' => 265000000, 'sewa' => 325000, 'kursi' => 7],
        ['nama' => 'Mitsubishi Xpander Ultimate', 'brand' => 'Mitsubishi', 'harga' => 310000000, 'sewa' => 375000, 'kursi' => 7],
        ['nama' => 'Daihatsu Terios R AT', 'brand' => 'Daihatsu', 'harga' => 295000000, 'sewa' => 350000, 'kursi' => 7],
        ['nama' => 'Toyota Innova Zenix Q Hybrid', 'brand' => 'Toyota', 'harga' => 620000000, 'sewa' => 750000, 'kursi' => 7],
        ['nama' => 'Honda HR-V 1.5 SE', 'brand' => 'Honda', 'harga' => 420000000, 'sewa' => 500000, 'kursi' => 5],
        ['nama' => 'Hyundai Creta Prime', 'brand' => 'Hyundai', 'harga' => 400000000, 'sewa' => 475000, 'kursi' => 5],
        ['nama' => 'Wuling Air EV Long Range', 'brand' => 'Wuling', 'harga' => 295000000, 'sewa' => 300000, 'kursi' => 4],
        ['nama' => 'Nissan Livina VL AT', 'brand' => 'Nissan', 'harga' => 280000000, 'sewa' => 340000, 'kursi' => 7],
    ];

    public function definition(): array
    {
        $model = $this->faker->randomElement(self::MODEL_MOBIL);
        $bahanBakar = str_contains($model['nama'], 'EV')
            ? 'Listrik'
            : $this->faker->randomElement(['Bensin', 'Bensin', 'Diesel', 'Hybrid']);

        return [
            'plat_nomor' => strtoupper($this->faker->unique()->bothify('B #### ???')),
            'nama' => $model['nama'],
            'brand' => $model['brand'],
            'harga' => $model['harga'],
            'harga_sewa_harian' => $model['sewa'],
            'tahun' => $this->faker->numberBetween(2018, 2025),
            'transmisi' => $this->faker->randomElement(['Manual', 'Matic', 'CVT']),
            'bahan_bakar' => $bahanBakar,
            'kapasitas_kursi' => $model['kursi'],
            'status' => 'tersedia',
            'foto' => 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=1200',
            'deskripsi' => "Unit {$model['nama']} terawat, servis rutin, siap disewa harian maupun mingguan.",
        ];
    }

    public function tersedia(): static
    {
        return $this->state(fn () => ['status' => 'tersedia']);
    }

    public function disewa(): static
    {
        return $this->state(fn () => ['status' => 'disewa']);
    }

    public function servis(): static
    {
        return $this->state(fn () => ['status' => 'servis']);
    }
}
