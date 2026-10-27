<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed database dengan data awal showroom.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            MobilSeeder::class,
            PelangganSeeder::class,
            PenyewaanSeeder::class,
        ]);
    }
}
