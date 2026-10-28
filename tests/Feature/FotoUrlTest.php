<?php

namespace Tests\Feature;

use App\Models\Mobil;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Menjaga URL foto tetap benar.
 *
 * Pernah terjadi di produksi: berkas terunggah dengan baik dan dapat diakses
 * lewat /storage/..., tetapi URL yang tersimpan di basis data rusak menjadi
 *
 *   APP_URL="https://app.cleverapps.io"/storage/mobil/xxxx.png
 *
 * Penyebabnya config/filesystems.php membaca env('APP_URL') mentah, melewati
 * normalisasi di config/app.php. Nilai APP_URL di Clever Cloud menyimpan
 * tanda kutip, sehingga tanda kutip itu ikut masuk ke URL. Gejalanya tidak
 * muncul di lokal karena .env lokal tidak memakai tanda kutip.
 *
 * Test ini mengunci dua hal: disk publik harus memakai config('app.url'),
 * dan respons unggah tidak boleh mengandung tanda kutip.
 */
class FotoUrlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->create());
    }

    /**
     * PNG 8x8 yang dibangun langsung dari byte, tanpa ekstensi GD.
     */
    private function pngKecil(): string
    {
        $w = 8;
        $h = 8;
        $mentah = '';

        for ($y = 0; $y < $h; $y++) {
            $mentah .= "\x00"; // filter type 0

            for ($x = 0; $x < $w; $x++) {
                $mentah .= chr(200).chr(60).chr(30);
            }
        }

        $potong = fn (string $tipe, string $data): string
            => pack('N', strlen($data)).$tipe.$data.pack('N', crc32($tipe.$data));

        return "\x89PNG\r\n\x1a\n"
            .$potong('IHDR', pack('NNCCCCC', $w, $h, 8, 2, 0, 0, 0))
            .$potong('IDAT', gzcompress($mentah, 9))
            .$potong('IEND', '');
    }

    private function mobil(): Mobil
    {
        return Mobil::create([
            'plat_nomor' => 'B 1234 FTS',
            'nama' => 'Toyota Avanza 1.5 G CVT',
            'brand' => 'Toyota',
            'harga' => 275000000,
            'harga_sewa_harian' => 350000,
            'tahun' => 2024,
            'transmisi' => 'CVT',
            'bahan_bakar' => 'Bensin',
            'kapasitas_kursi' => 7,
        ]);
    }

    public function test_disk_publik_memakai_app_url_yang_sudah_dinormalkan(): void
    {
        $this->assertSame(
            config('app.url').'/storage',
            config('filesystems.disks.public.url'),
            'URL disk publik harus diturunkan dari config("app.url"), bukan env("APP_URL") mentah.'
        );
    }

    /**
     * Penjaga statis terhadap akar bug.
     *
     * Perbandingan config di atas tidak cukup: di lingkungan test APP_URL
     * tidak bertanda kutip, sehingga env('APP_URL') dan config('app.url')
     * bernilai sama dan keduanya akan lolos. Yang membedakan hanya cara
     * pembacaannya, jadi yang diperiksa adalah sumber nilainya.
     */
    public function test_filesystems_tidak_membaca_env_app_url_secara_langsung(): void
    {
        $isi = file_get_contents(config_path('filesystems.php'));

        /*
         * Komentar dibuang lebih dulu. Berkas itu memang menyebut
         * env('APP_URL') di dalam penjelasan mengapa pembacaan langsung
         * berbahaya, dan sebutan di komentar bukan pelanggaran.
         */
        $kode = '';
        foreach (token_get_all($isi) as $token) {
            if (is_array($token)) {
                if (in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                    continue;
                }
                $kode .= $token[1];
            } else {
                $kode .= $token;
            }
        }

        $this->assertStringNotContainsString(
            "env('APP_URL')",
            $kode,
            'config/filesystems.php tidak boleh membaca env("APP_URL") langsung; gunakan config("app.url") agar normalisasi di config/app.php ikut berlaku.'
        );
    }

    public function test_url_disk_publik_tidak_mengandung_tanda_kutip(): void
    {
        $url = config('filesystems.disks.public.url');

        $this->assertStringNotContainsString('"', $url);
        $this->assertStringNotContainsString("'", $url);
        $this->assertMatchesRegularExpression('#^https?://#', $url);
    }

    public function test_upload_foto_mengembalikan_url_yang_bersih(): void
    {
        Storage::fake('public');

        $mobil = $this->mobil();

        $berkas = UploadedFile::fake()->createWithContent('foto.png', $this->pngKecil());

        $respons = $this->postJson("/api/mobil/{$mobil->id}/foto", ['foto' => $berkas]);

        $respons->assertOk();

        $url = $respons->json('data.foto');

        $this->assertIsString($url);
        $this->assertStringNotContainsString('"', $url, 'URL foto tidak boleh memuat tanda kutip.');
        $this->assertStringNotContainsString('APP_URL', $url, 'Nama variabel tidak boleh bocor ke URL.');
        $this->assertStringContainsString('/storage/', $url);

        /*
         * Storage::fake() mengganti konfigurasi disk menjadi root sementara
         * dengan URL relatif, sehingga awalan domain tidak dapat diperiksa di
         * sini. Yang diperiksa adalah bagian yang menjadi inti bug: URL tidak
         * boleh memuat sisa nama variabel atau tanda kutip. Awalan domain
         * dijaga oleh test_disk_publik_memakai_app_url_yang_sudah_dinormalkan().
         */
        $jalur = preg_replace('#^.*?/storage/#', '', $url);
        Storage::disk('public')->assertExists($jalur);
    }

    public function test_upload_foto_menolak_berkas_bukan_gambar(): void
    {
        Storage::fake('public');

        $mobil = $this->mobil();

        $berkas = UploadedFile::fake()->createWithContent('dokumen.pdf', '%PDF-1.4 contoh');

        $this->postJson("/api/mobil/{$mobil->id}/foto", ['foto' => $berkas])
            ->assertStatus(422)
            ->assertJsonValidationErrors('foto');
    }
}
