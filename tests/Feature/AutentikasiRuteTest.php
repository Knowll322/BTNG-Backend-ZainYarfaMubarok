<?php

namespace Tests\Feature;

use App\Models\Mobil;
use App\Models\Pelanggan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Memastikan batas keamanan rute benar-benar berlaku.
 *
 * Rute baca dibuka untuk umum agar penguji dapat langsung mencoba GET,
 * sedangkan seluruh rute tulis wajib token Sanctum. Test ini menjaga agar
 * batas tersebut tidak terbuka kembali tanpa sengaja di kemudian hari.
 */
class AutentikasiRuteTest extends TestCase
{
    use RefreshDatabase;

    public function test_rute_baca_dapat_diakses_tanpa_token(): void
    {
        $mobil = Mobil::factory()->create();
        $pelanggan = Pelanggan::factory()->create();

        $this->getJson('/api/mobil')->assertOk();
        $this->getJson('/api/mobil/'.$mobil->id)->assertOk();
        $this->getJson('/api/pelanggan')->assertOk();
        $this->getJson('/api/pelanggan/'.$pelanggan->id)->assertOk();
        $this->getJson('/api/penyewaan')->assertOk();
        $this->getJson('/api/statistik')->assertOk();
        $this->getJson('/api/health')->assertOk();
    }

    public function test_store_mobil_tanpa_token_ditolak_401(): void
    {
        $response = $this->postJson('/api/mobil', [
            'plat_nomor' => 'B 9999 TST',
            'nama' => 'Mobil Tanpa Token',
            'brand' => 'Toyota',
            'harga' => 100000000,
            'harga_sewa_harian' => 200000,
            'tahun' => 2024,
            'transmisi' => 'Matic',
            'bahan_bakar' => 'Bensin',
            'kapasitas_kursi' => 5,
        ]);

        $response->assertUnauthorized()
            ->assertJsonPath('data', null)
            ->assertJsonStructure(['message', 'data']);

        $this->assertDatabaseMissing('mobils', ['plat_nomor' => 'B 9999 TST']);
    }

    public function test_update_mobil_tanpa_token_ditolak_401(): void
    {
        $mobil = Mobil::factory()->create(['nama' => 'Nama Asli']);

        $this->putJson('/api/mobil/'.$mobil->id, ['nama' => 'Nama Palsu'])
            ->assertUnauthorized();

        $this->patchJson('/api/mobil/'.$mobil->id, ['nama' => 'Nama Palsu'])
            ->assertUnauthorized();

        $this->assertDatabaseHas('mobils', ['id' => $mobil->id, 'nama' => 'Nama Asli']);
    }

    public function test_destroy_mobil_tanpa_token_ditolak_401(): void
    {
        $mobil = Mobil::factory()->create();

        $this->deleteJson('/api/mobil/'.$mobil->id)->assertUnauthorized();

        $this->assertDatabaseHas('mobils', ['id' => $mobil->id]);
    }

    public function test_rute_tulis_pelanggan_tanpa_token_ditolak_401(): void
    {
        $pelanggan = Pelanggan::factory()->create();

        $this->postJson('/api/pelanggan', [])->assertUnauthorized();
        $this->putJson('/api/pelanggan/'.$pelanggan->id, [])->assertUnauthorized();
        $this->patchJson('/api/pelanggan/'.$pelanggan->id, [])->assertUnauthorized();
        $this->deleteJson('/api/pelanggan/'.$pelanggan->id)->assertUnauthorized();

        $this->assertDatabaseHas('pelanggans', ['id' => $pelanggan->id]);
    }

    public function test_rute_tulis_penyewaan_tanpa_token_ditolak_401(): void
    {
        $mobil = Mobil::factory()->create();
        $pelanggan = Pelanggan::factory()->create();

        $this->postJson('/api/penyewaan', [])->assertUnauthorized();
        $this->deleteJson('/api/penyewaan/1')->assertUnauthorized();
        $this->postJson('/api/penyewaan/1/kembalikan', [])->assertUnauthorized();
        $this->postJson('/api/penyewaan/1/batalkan')->assertUnauthorized();

        $this->assertDatabaseCount('penyewaans', 0);
    }

    public function test_pesan_401_menuntun_ke_endpoint_login(): void
    {
        $response = $this->postJson('/api/mobil', []);

        $response->assertUnauthorized();

        $pesan = $response->json('message');

        $this->assertIsString($pesan);
        $this->assertStringContainsString('/api/auth/login', $pesan);
        $this->assertStringContainsString('Bearer', $pesan);
    }

    public function test_store_mobil_dengan_token_berhasil_201(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/mobil', [
            'plat_nomor' => 'B 8888 TST',
            'nama' => 'Mobil Bertoken',
            'brand' => 'Honda',
            'harga' => 150000000,
            'harga_sewa_harian' => 250000,
            'tahun' => 2024,
            'transmisi' => 'CVT',
            'bahan_bakar' => 'Bensin',
            'kapasitas_kursi' => 5,
        ])->assertCreated()
            ->assertJsonPath('data.plat_nomor', 'B 8888 TST');

        $this->assertDatabaseHas('mobils', ['plat_nomor' => 'B 8888 TST']);
    }

    public function test_login_menghasilkan_token_yang_dapat_dipakai(): void
    {
        $user = User::factory()->create([
            'email' => 'penguji@showroom.test',
            'password' => bcrypt('password123'),
        ]);

        $login = $this->postJson('/api/auth/login', [
            'email' => 'penguji@showroom.test',
            'password' => 'password123',
        ])->assertOk();

        $token = $login->json('data.token');

        $this->assertNotEmpty($token);

        // Token dari login benar-benar membuka rute tulis.
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/pelanggan', [
                'nama' => 'Pelanggan Lewat Token',
                'nik' => '3273010101010009',
                'email' => 'token@example.com',
                'telepon' => '081234567890',
                'alamat' => 'Jl. Token No. 1',
                'nomor_sim' => 'SIM999999',
            ])->assertCreated();

        $this->assertDatabaseHas('pelanggans', ['email' => 'token@example.com']);
    }
}
