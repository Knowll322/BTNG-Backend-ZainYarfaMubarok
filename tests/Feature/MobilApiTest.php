<?php

namespace Tests\Feature;

use App\Models\Mobil;
use App\Models\Pelanggan;
use App\Models\Penyewaan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobilApiTest extends TestCase
{
    use RefreshDatabase;

    private function dataMobil(array $override = []): array
    {
        return array_merge([
            'plat_nomor' => 'B 1234 TST',
            'nama' => 'Toyota Avanza 1.5 G CVT',
            'brand' => 'Toyota',
            'harga' => 275000000,
            'harga_sewa_harian' => 350000,
            'tahun' => 2024,
            'transmisi' => 'CVT',
            'bahan_bakar' => 'Bensin',
            'kapasitas_kursi' => 7,
        ], $override);
    }

    public function test_index_mengembalikan_amplop_message_dan_data(): void
    {
        Mobil::factory()->count(3)->create();

        $response = $this->getJson('/api/mobil');

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [['id', 'plat_nomor', 'nama', 'brand', 'harga', 'harga_sewa_harian', 'tahun', 'transmisi', 'bahan_bakar', 'status']],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
                'links' => ['first', 'prev', 'next', 'last'],
            ])
            ->assertJsonPath('meta.total', 3);
    }

    public function test_store_membuat_mobil_dan_mengembalikan_201(): void
    {
        $response = $this->postJson('/api/mobil', $this->dataMobil());

        $response->assertCreated()
            ->assertJsonPath('message', 'Data mobil berhasil ditambahkan')
            ->assertJsonPath('data.plat_nomor', 'B 1234 TST')
            ->assertJsonPath('data.status', 'tersedia');

        $this->assertDatabaseHas('mobils', ['plat_nomor' => 'B 1234 TST']);
    }

    public function test_store_gagal_validasi_mengembalikan_422_dengan_errors(): void
    {
        $response = $this->postJson('/api/mobil', [
            'plat_nomor' => '',
            'harga' => 'mahal',
            'transmisi' => 'Otomatis',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'data', 'errors'])
            ->assertJsonValidationErrors(['plat_nomor', 'nama', 'brand', 'harga', 'transmisi']);

        // Pesan ringkasan tidak boleh terduplikasi menjadi
        // "... (and 8 more errors) (and 8 more error)".
        $this->assertSame(1, substr_count($response->json('message'), '(and '));
    }

    public function test_show_mengembalikan_detail_dan_404_bila_tidak_ada(): void
    {
        $mobil = Mobil::factory()->create();

        $this->getJson("/api/mobil/{$mobil->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $mobil->id);

        $this->getJson('/api/mobil/99999')
            ->assertNotFound()
            ->assertJsonPath('message', 'Data tidak ditemukan.');
    }

    public function test_update_mengubah_data_mobil(): void
    {
        $mobil = Mobil::factory()->create(['status' => 'tersedia']);

        $this->patchJson("/api/mobil/{$mobil->id}", [
            'status' => 'servis',
            'harga_sewa_harian' => 400000,
        ])->assertOk()
            ->assertJsonPath('data.status', 'servis')
            ->assertJsonPath('data.harga_sewa_harian', 400000);
    }

    public function test_destroy_menghapus_mobil(): void
    {
        $mobil = Mobil::factory()->create();

        $this->deleteJson("/api/mobil/{$mobil->id}")->assertOk();

        $this->assertDatabaseMissing('mobils', ['id' => $mobil->id]);
    }

    public function test_destroy_menolak_409_bila_masih_punya_riwayat_penyewaan(): void
    {
        $mobil = Mobil::factory()->create();
        Penyewaan::factory()->create([
            'mobil_id' => $mobil->id,
            'pelanggan_id' => Pelanggan::factory()->create()->id,
        ]);

        $this->deleteJson("/api/mobil/{$mobil->id}")
            ->assertStatus(409)
            ->assertJsonPath('data', null);

        $this->assertDatabaseHas('mobils', ['id' => $mobil->id]);
    }

    public function test_filter_dan_search_bekerja(): void
    {
        Mobil::factory()->create(['brand' => 'Toyota', 'status' => 'tersedia', 'nama' => 'Avanza']);
        Mobil::factory()->create(['brand' => 'Honda', 'status' => 'servis', 'nama' => 'Brio']);

        $this->getJson('/api/mobil?brand=Toyota')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->getJson('/api/mobil?search=Brio')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.brand', 'Honda');

        $this->getJson('/api/mobil?status=servis')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_endpoint_tidak_dikenal_mengembalikan_json_404(): void
    {
        $this->getJson('/api/tidak-ada')
            ->assertNotFound()
            ->assertJsonPath('message', 'Endpoint atau data tidak ditemukan.');
    }

    public function test_health_endpoint(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJsonPath('data.status', 'ok');
    }

    public function test_auth_login_dan_me(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $login = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertOk()->assertJsonStructure(['message', 'data' => ['user', 'token']]);

        $token = $login->json('data.token');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_auth_me_tanpa_token_401(): void
    {
        $this->getJson('/api/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('data', null)
            ->assertJsonStructure(['message', 'data']);
    }
}
