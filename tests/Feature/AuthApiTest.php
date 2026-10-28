<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Perilaku endpoint autentikasi.
 *
 * Dipisahkan dari MobilApiTest karena file itu mengautentikasi dirinya di
 * setUp() demi rute tulis, sedangkan test di sini justru harus berjalan
 * tanpa token untuk membuktikan 401 benar-benar terjadi.
 */
class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_dan_me(): void
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

    public function test_me_tanpa_token_401(): void
    {
        $this->getJson('/api/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('data', null)
            ->assertJsonStructure(['message', 'data']);
    }

    public function test_login_dengan_password_salah_ditolak(): void
    {
        User::factory()->create([
            'email' => 'salah@showroom.test',
            'password' => bcrypt('password123'),
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'salah@showroom.test',
            'password' => 'password-salah',
        ])->assertStatus(422);
    }

    public function test_register_membuat_akun_baru(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Pengguna Baru',
            'email' => 'baru@showroom.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated();

        $this->assertDatabaseHas('users', ['email' => 'baru@showroom.test']);
    }

    public function test_logout_mencabut_token(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $token = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->json('data.token');

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/logout')
            ->assertOk();

        /*
         * Baris token harus benar-benar hilang dari basis data.
         *
         * Sengaja tidak diuji dengan request /auth/me kedua: dalam satu test,
         * guard auth menyimpan pengguna yang sudah ter-resolve sehingga
         * request berikutnya lolos tanpa memeriksa ulang token. Pemeriksaan
         * perilaku sesungguhnya dilakukan pada server live.
         */
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
