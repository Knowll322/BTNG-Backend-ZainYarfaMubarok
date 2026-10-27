<?php

namespace Tests\Feature;

use App\Models\Mobil;
use App\Models\Pelanggan;
use App\Models\Penyewaan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenyewaanApiTest extends TestCase
{
    use RefreshDatabase;

    private function mobil(array $override = []): Mobil
    {
        return Mobil::factory()->create(array_merge([
            'status' => 'tersedia',
            'harga_sewa_harian' => 300000,
        ], $override));
    }

    private function pelanggan(): Pelanggan
    {
        return Pelanggan::factory()->create();
    }

    public function test_store_menghitung_durasi_dan_total_otomatis(): void
    {
        $mobil = $this->mobil();
        $pelanggan = $this->pelanggan();

        $response = $this->postJson('/api/penyewaan', [
            'mobil_id' => $mobil->id,
            'pelanggan_id' => $pelanggan->id,
            'tanggal_mulai' => now()->addDays(2)->toDateString(),
            'tanggal_selesai' => now()->addDays(4)->toDateString(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Penyewaan berhasil dibuat')
            ->assertJsonPath('data.durasi_hari', 3)
            ->assertJsonPath('data.harga_sewa_per_hari', 300000)
            ->assertJsonPath('data.total_biaya', 900000)
            ->assertJsonPath('data.status', 'booked');

        $this->assertMatchesRegularExpression('/^BK-\d{8}-[A-Z0-9]{5}$/', $response->json('data.kode_booking'));
    }

    public function test_store_menolak_jadwal_bentrok_untuk_mobil_yang_sama(): void
    {
        $mobil = $this->mobil();
        $pelanggan = $this->pelanggan();

        Penyewaan::factory()->create([
            'mobil_id' => $mobil->id,
            'pelanggan_id' => $pelanggan->id,
            'tanggal_mulai' => now()->addDays(5)->toDateString(),
            'tanggal_selesai' => now()->addDays(8)->toDateString(),
            'status' => 'booked',
        ]);

        $this->postJson('/api/penyewaan', [
            'mobil_id' => $mobil->id,
            'pelanggan_id' => $pelanggan->id,
            'tanggal_mulai' => now()->addDays(7)->toDateString(),
            'tanggal_selesai' => now()->addDays(9)->toDateString(),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['tanggal_mulai']);
    }

    public function test_store_menolak_mobil_servis(): void
    {
        $mobil = $this->mobil(['status' => 'servis']);

        $this->postJson('/api/penyewaan', [
            'mobil_id' => $mobil->id,
            'pelanggan_id' => $this->pelanggan()->id,
            'tanggal_mulai' => now()->addDay()->toDateString(),
            'tanggal_selesai' => now()->addDays(3)->toDateString(),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['mobil_id']);
    }

    public function test_store_menolak_tanggal_selesai_sebelum_mulai(): void
    {
        $this->postJson('/api/penyewaan', [
            'mobil_id' => $this->mobil()->id,
            'pelanggan_id' => $this->pelanggan()->id,
            'tanggal_mulai' => now()->addDays(5)->toDateString(),
            'tanggal_selesai' => now()->addDays(2)->toDateString(),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['tanggal_selesai']);
    }

    public function test_status_mobil_menjadi_disewa_saat_penyewaan_berjalan(): void
    {
        $mobil = $this->mobil();

        $this->postJson('/api/penyewaan', [
            'mobil_id' => $mobil->id,
            'pelanggan_id' => $this->pelanggan()->id,
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->addDays(2)->toDateString(),
            'status' => 'berjalan',
        ])->assertCreated();

        $this->assertSame('disewa', $mobil->fresh()->status);
    }

    public function test_kembalikan_menghitung_denda_keterlambatan(): void
    {
        $mobil = $this->mobil(['harga_sewa_harian' => 200000, 'status' => 'disewa']);
        $penyewaan = Penyewaan::factory()->create([
            'mobil_id' => $mobil->id,
            'pelanggan_id' => $this->pelanggan()->id,
            'tanggal_mulai' => now()->subDays(10)->toDateString(),
            'tanggal_selesai' => now()->subDays(5)->toDateString(),
            'durasi_hari' => 6,
            'harga_sewa_per_hari' => 200000,
            'total_biaya' => 1200000,
            'status' => 'berjalan',
        ]);

        // Terlambat 2 hari x 50% x 200.000 = 200.000
        $response = $this->postJson("/api/penyewaan/{$penyewaan->id}/kembalikan", [
            'tanggal_dikembalikan' => now()->subDays(3)->toDateString(),
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'selesai')
            ->assertJsonPath('data.denda', 200000);

        $this->assertSame('tersedia', $mobil->fresh()->status);
    }

    public function test_kembalikan_tanpa_denda_bila_tepat_waktu(): void
    {
        $mobil = $this->mobil(['status' => 'disewa']);
        $penyewaan = Penyewaan::factory()->create([
            'mobil_id' => $mobil->id,
            'pelanggan_id' => $this->pelanggan()->id,
            'tanggal_mulai' => now()->subDays(5)->toDateString(),
            'tanggal_selesai' => now()->toDateString(),
            'status' => 'berjalan',
        ]);

        $this->postJson("/api/penyewaan/{$penyewaan->id}/kembalikan")
            ->assertOk()
            ->assertJsonPath('data.denda', 0);
    }

    public function test_kembalikan_dua_kali_ditolak_422(): void
    {
        $penyewaan = Penyewaan::factory()->create([
            'mobil_id' => $this->mobil()->id,
            'pelanggan_id' => $this->pelanggan()->id,
            'status' => 'selesai',
            'tanggal_dikembalikan' => now()->toDateString(),
        ]);

        $this->postJson("/api/penyewaan/{$penyewaan->id}/kembalikan")
            ->assertStatus(422);
    }

    public function test_batalkan_mengubah_status_dan_melepas_unit(): void
    {
        $mobil = $this->mobil(['status' => 'disewa']);
        $penyewaan = Penyewaan::factory()->create([
            'mobil_id' => $mobil->id,
            'pelanggan_id' => $this->pelanggan()->id,
            'tanggal_mulai' => now()->subDays(2)->toDateString(),
            'tanggal_selesai' => now()->addDays(2)->toDateString(),
            'status' => 'berjalan',
        ]);

        $this->postJson("/api/penyewaan/{$penyewaan->id}/batalkan")
            ->assertOk()
            ->assertJsonPath('data.status', 'batal');

        $this->assertSame('tersedia', $mobil->fresh()->status);
    }

    public function test_index_menyertakan_relasi_dan_filter_status(): void
    {
        $mobil = $this->mobil();
        $pelanggan = $this->pelanggan();

        Penyewaan::factory()->create([
            'mobil_id' => $mobil->id,
            'pelanggan_id' => $pelanggan->id,
            'status' => 'selesai',
        ]);
        Penyewaan::factory()->create([
            'mobil_id' => $this->mobil()->id,
            'pelanggan_id' => $pelanggan->id,
            'status' => 'booked',
        ]);

        $this->getJson('/api/penyewaan?status=selesai')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonStructure(['data' => [['id', 'kode_booking', 'mobil' => ['id', 'nama'], 'pelanggan' => ['id', 'nama']]]]);
    }

    public function test_statistik_meringkas_data(): void
    {
        $this->mobil();
        $this->pelanggan();

        $this->getJson('/api/statistik')
            ->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'total_mobil', 'mobil_per_status', 'total_pelanggan', 'total_penyewaan',
                    'penyewaan_per_status', 'pendapatan_total', 'pendapatan_bulan_ini',
                    'mobil_terpopuler', 'penyewaan_terbaru',
                ],
            ])
            ->assertJsonPath('data.total_mobil', 1)
            ->assertJsonPath('data.total_pelanggan', 1);
    }
}
