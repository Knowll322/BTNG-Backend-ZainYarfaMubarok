<?php

namespace Database\Factories;

use App\Models\Pelanggan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pelanggan>
 */
class PelangganFactory extends Factory
{
    protected $model = Pelanggan::class;

    public function definition(): array
    {
        return [
            'nama' => $this->faker->name(),
            'nik' => $this->faker->unique()->numerify('################'),
            'email' => $this->faker->unique()->safeEmail(),
            'telepon' => '08'.$this->faker->numerify('##########'),
            'alamat' => $this->faker->streetAddress().', '.$this->faker->city().', '.$this->faker->state(),
            'nomor_sim' => 'SIM-'.$this->faker->unique()->numerify('############'),
            'foto_ktp' => null,
        ];
    }
}
