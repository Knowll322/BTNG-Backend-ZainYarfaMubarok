<?php

namespace Tests\Feature;

use App\Models\Mobil;
use App\Models\Pelanggan;
use App\Models\Penyewaan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PelangganApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Rute tulis dijaga auth:sanctum; test bertindak sebagai pengguna
        // terautentikasi. Rute GET tetap publik.
        Sanctum::actingAs(User::factory()->create());
    }

    private function dataPelanggan(array $override = []): array
    {
        return array_merge([
            'nama' => 'Zain Yarfa Mubarok',
            'nik' => '3273010101010001',
            'email' => 'zain@example.com',
            'telepon' => '081234567001',
            'alamat' => 'Jl. Cihampelas No. 112, Bandung',
            'nomor_sim' => 'SIM-327301010101',
        ], $override);
    }

    public function test_store_membuat_pelanggan(): void
    {
        $this->postJson('/api/pelanggan', $this->dataPelanggan())
            ->assertCreated()
            ->assertJsonPath('data.nama', 'Zain Yarfa Mubarok');

        $this->assertDatabaseHas('pelanggans', ['email' => 'zain@example.com']);
    }

    public function test_store_menolak_nik_bukan_16_digit(): void
    {
        $this->postJson('/api/pelanggan', $this->dataPelanggan(['nik' => '123']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nik']);
    }

    public function test_store_menolak_email_duplikat(): void
    {
        Pelanggan::factory()->create(['email' => 'zain@example.com']);

        $this->postJson('/api/pelanggan', $this->dataPelanggan())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_index_bisa_dicari(): void
    {
        Pelanggan::factory()->create(['nama' => 'Zain Yarfa']);
        Pelanggan::factory()->create(['nama' => 'Rania Dewi']);

        $this->getJson('/api/pelanggan?search=Zain')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.nama', 'Zain Yarfa');
    }

    public function test_update_dan_destroy(): void
    {
        $pelanggan = Pelanggan::factory()->create();

        $this->patchJson("/api/pelanggan/{$pelanggan->id}", ['nama' => 'Nama Baru'])
            ->assertOk()
            ->assertJsonPath('data.nama', 'Nama Baru');

        $this->deleteJson("/api/pelanggan/{$pelanggan->id}")->assertOk();

        $this->assertDatabaseMissing('pelanggans', ['id' => $pelanggan->id]);
    }

    public function test_destroy_menolak_409_bila_punya_riwayat(): void
    {
        $pelanggan = Pelanggan::factory()->create();
        Penyewaan::factory()->create([
            'pelanggan_id' => $pelanggan->id,
            'mobil_id' => Mobil::factory()->create()->id,
        ]);

        $this->deleteJson("/api/pelanggan/{$pelanggan->id}")->assertStatus(409);
    }

    public function test_show_404_untuk_id_tidak_ada(): void
    {
        $this->getJson('/api/pelanggan/9999')
            ->assertNotFound()
            ->assertJsonPath('data', null);
    }
}
