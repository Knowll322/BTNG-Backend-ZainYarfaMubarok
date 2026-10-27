<?php

namespace Database\Seeders;

use App\Models\Pelanggan;
use Illuminate\Database\Seeder;

class PelangganSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'nama' => 'Zain Yarfa Mubarok',
                'nik' => '3273010101010001',
                'email' => 'zain.yarfa@example.com',
                'telepon' => '081234567001',
                'alamat' => 'Jl. Cihampelas No. 112, Bandung, Jawa Barat',
                'nomor_sim' => 'SIM-327301010101',
            ],
            [
                'nama' => 'Rania Puspita Dewi',
                'nik' => '3273010101010002',
                'email' => 'rania.puspita@example.com',
                'telepon' => '081234567002',
                'alamat' => 'Jl. Dago Asri No. 8, Bandung, Jawa Barat',
                'nomor_sim' => 'SIM-327301010102',
            ],
            [
                'nama' => 'Bagas Prasetyo',
                'nik' => '3273010101010003',
                'email' => 'bagas.prasetyo@example.com',
                'telepon' => '081234567003',
                'alamat' => 'Jl. Riau No. 45, Bandung, Jawa Barat',
                'nomor_sim' => 'SIM-327301010103',
            ],
            [
                'nama' => 'Salsabila Nur Aisyah',
                'nik' => '3273010101010004',
                'email' => 'salsabila.nur@example.com',
                'telepon' => '081234567004',
                'alamat' => 'Jl. Setiabudi No. 210, Bandung, Jawa Barat',
                'nomor_sim' => 'SIM-327301010104',
            ],
            [
                'nama' => 'Fajar Ramadhan',
                'nik' => '3273010101010005',
                'email' => 'fajar.ramadhan@example.com',
                'telepon' => '081234567005',
                'alamat' => 'Jl. Buah Batu No. 77, Bandung, Jawa Barat',
                'nomor_sim' => 'SIM-327301010105',
            ],
            [
                'nama' => 'Melati Ayu Lestari',
                'nik' => '3273010101010006',
                'email' => 'melati.ayu@example.com',
                'telepon' => '081234567006',
                'alamat' => 'Jl. Pasteur No. 33, Bandung, Jawa Barat',
                'nomor_sim' => 'SIM-327301010106',
            ],
            [
                'nama' => 'Dimas Aryo Wibowo',
                'nik' => '3273010101010007',
                'email' => 'dimas.aryo@example.com',
                'telepon' => '081234567007',
                'alamat' => 'Jl. Gatot Subroto No. 190, Bandung, Jawa Barat',
                'nomor_sim' => 'SIM-327301010107',
            ],
            [
                'nama' => 'Intan Permata Sari',
                'nik' => '3273010101010008',
                'email' => 'intan.permata@example.com',
                'telepon' => '081234567008',
                'alamat' => 'Jl. Sukajadi No. 64, Bandung, Jawa Barat',
                'nomor_sim' => 'SIM-327301010108',
            ],
            [
                'nama' => 'Rizky Hidayatullah',
                'nik' => '3273010101010009',
                'email' => 'rizky.hidayat@example.com',
                'telepon' => '081234567009',
                'alamat' => 'Jl. Ahmad Yani No. 255, Bandung, Jawa Barat',
                'nomor_sim' => 'SIM-327301010109',
            ],
            [
                'nama' => 'Nadia Rahmawati',
                'nik' => '3273010101010010',
                'email' => 'nadia.rahma@example.com',
                'telepon' => '081234567010',
                'alamat' => 'Jl. Lengkong Besar No. 12, Bandung, Jawa Barat',
                'nomor_sim' => 'SIM-327301010110',
            ],
        ];

        foreach ($data as $pelanggan) {
            Pelanggan::updateOrCreate(['nik' => $pelanggan['nik']], $pelanggan);
        }
    }
}
