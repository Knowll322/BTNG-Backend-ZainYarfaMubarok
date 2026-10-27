<?php

namespace Database\Seeders;

use App\Models\Mobil;
use App\Models\Pelanggan;
use App\Models\Penyewaan;
use Illuminate\Database\Seeder;

class PenyewaanSeeder extends Seeder
{
    /**
     * Denda keterlambatan 50% dari harga sewa harian per hari.
     */
    private const PERSEN_DENDA = 0.5;

    public function run(): void
    {
        if (Penyewaan::query()->exists()) {
            return;
        }

        $mobils = Mobil::query()->orderBy('id')->get();
        $pelanggans = Pelanggan::query()->orderBy('id')->get();

        if ($mobils->count() < 8 || $pelanggans->count() < 8) {
            return;
        }

        $rencana = [
            // 2 berjalan (mobil saat ini dipakai)
            ['mobil' => 0, 'pelanggan' => 0, 'mulai' => -2, 'selesai' => 3, 'status' => 'berjalan'],
            ['mobil' => 1, 'pelanggan' => 1, 'mulai' => -1, 'selesai' => 4, 'status' => 'berjalan'],

            // 2 booked (jadwal ke depan, tidak bentrok dengan yang berjalan)
            ['mobil' => 2, 'pelanggan' => 2, 'mulai' => 7, 'selesai' => 10, 'status' => 'booked'],
            ['mobil' => 3, 'pelanggan' => 3, 'mulai' => 12, 'selesai' => 15, 'status' => 'booked'],

            // 3 selesai, satu di antaranya terlambat 3 hari
            ['mobil' => 4, 'pelanggan' => 4, 'mulai' => -20, 'selesai' => -15, 'status' => 'selesai', 'kembali' => -15, 'telat' => 0],
            ['mobil' => 5, 'pelanggan' => 5, 'mulai' => -30, 'selesai' => -24, 'status' => 'selesai', 'kembali' => -24, 'telat' => 0],
            ['mobil' => 6, 'pelanggan' => 6, 'mulai' => -40, 'selesai' => -35, 'status' => 'selesai', 'kembali' => -32, 'telat' => 3],

            // 1 batal
            ['mobil' => 7, 'pelanggan' => 7, 'mulai' => 20, 'selesai' => 22, 'status' => 'batal'],
        ];

        foreach ($rencana as $i => $row) {
            $mobil = $mobils[$row['mobil']];
            $mulai = now()->startOfDay()->addDays($row['mulai']);
            $selesai = now()->startOfDay()->addDays($row['selesai']);
            $durasi = (int) $mulai->diffInDays($selesai) + 1;
            $hargaPerHari = (int) $mobil->harga_sewa_harian;
            $denda = isset($row['telat']) && $row['telat'] > 0
                ? (int) round($row['telat'] * $hargaPerHari * self::PERSEN_DENDA)
                : 0;

            $kode = 'BK-'.$mulai->format('Ymd').'-'.str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT);

            Penyewaan::create([
                'kode_booking' => $kode,
                'mobil_id' => $mobil->id,
                'pelanggan_id' => $pelanggans[$row['pelanggan']]->id,
                'tanggal_mulai' => $mulai->toDateString(),
                'tanggal_selesai' => $selesai->toDateString(),
                'durasi_hari' => $durasi,
                'harga_sewa_per_hari' => $hargaPerHari,
                'total_biaya' => $durasi * $hargaPerHari,
                'denda' => $denda,
                'status' => $row['status'],
                'tanggal_dikembalikan' => isset($row['kembali'])
                    ? now()->startOfDay()->addDays($row['kembali'])->toDateString()
                    : null,
                'catatan' => match ($row['status']) {
                    'berjalan' => 'Unit sedang digunakan, pengembalian sesuai jadwal.',
                    'booked' => 'Booking dikonfirmasi, DP sudah diterima.',
                    'selesai' => $denda > 0
                        ? 'Dikembalikan terlambat 3 hari, denda keterlambatan dibebankan.'
                        : 'Unit dikembalikan tepat waktu dalam kondisi baik.',
                    'batal' => 'Dibatalkan oleh pelanggan karena perubahan rencana.',
                    default => null,
                },
            ]);
        }

        // Sinkronkan status unit yang sedang dipakai.
        $mobilBerjalan = Penyewaan::query()
            ->where('status', 'berjalan')
            ->pluck('mobil_id');

        Mobil::whereIn('id', $mobilBerjalan)->update(['status' => 'disewa']);
    }
}
