<?php

namespace Tests\Unit;

use App\Support\AppUrl;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Mengunci normalisasi APP_URL.
 *
 * Bug ini sudah dua kali lolos ke produksi dengan gejala berbeda:
 *
 *  1. APP_URL="https://..."  -> URL foto menjadi
 *     APP_URL="https://..."/storage/mobil/xxx.png (tidak dapat dibuka).
 *  2. Seluruh baris tersimpan sebagai nilai, yaitu APP_URL=https://...
 *     di dalam variabel APP_URL. Validasi gagal, nilai jatuh ke cadangan,
 *     dan URL foto menjadi http://localhost/storage/mobil/xxx.png —
 *     tampak "bersih" sehingga lolos pemeriksaan sekilas, padahal salah
 *     domain dan tidak dapat dibuka dari luar.
 *
 * Keduanya sama-sama menghasilkan foto yang tidak tampil, jadi keduanya
 * diuji di sini.
 */
class AppUrlTest extends TestCase
{
    private const BERSIH = 'https://app-f4092249-75ca-459c-a6be-782e7a51f20c.cleverapps.io';

    public static function bentukKotor(): array
    {
        return [
            'normal' => ['https://app-f4092249-75ca-459c-a6be-782e7a51f20c.cleverapps.io'],
            'bertanda kutip ganda' => ['"https://app-f4092249-75ca-459c-a6be-782e7a51f20c.cleverapps.io"'],
            'bertanda kutip tunggal' => ["'https://app-f4092249-75ca-459c-a6be-782e7a51f20c.cleverapps.io'"],
            'baris utuh' => ['APP_URL="https://app-f4092249-75ca-459c-a6be-782e7a51f20c.cleverapps.io"'],
            'baris utuh tanpa kutip' => ['APP_URL=https://app-f4092249-75ca-459c-a6be-782e7a51f20c.cleverapps.io'],
            'baris utuh berspasi' => ['APP_URL = https://app-f4092249-75ca-459c-a6be-782e7a51f20c.cleverapps.io'],
            'berspasi di ujung' => ['   https://app-f4092249-75ca-459c-a6be-782e7a51f20c.cleverapps.io   '],
            'garis miring di akhir' => ['https://app-f4092249-75ca-459c-a6be-782e7a51f20c.cleverapps.io/'],
            'garis miring ganda' => ['https://app-f4092249-75ca-459c-a6be-782e7a51f20c.cleverapps.io//'],
            'baris utuh + kutip + garis miring' => ['APP_URL="https://app-f4092249-75ca-459c-a6be-782e7a51f20c.cleverapps.io/"'],
        ];
    }

    #[DataProvider('bentukKotor')]
    public function test_semua_bentuk_kotor_menghasilkan_url_bersih(string $mentah): void
    {
        $this->assertSame(self::BERSIH, AppUrl::normalisasi($mentah));
    }

    public static function bentukTidakDapatDipakai(): array
    {
        return [
            'kosong' => [''],
            'null' => [null],
            'hanya spasi' => ['   '],
            'hanya kutip' => ['""'],
            'placeholder panduan' => ['https://<domain-anda>'],
            'placeholder sudut' => ['https://<app-anda>.cleverapps.io'],
            'tanpa skema' => ['app-f4092249.cleverapps.io'],
            'skema salah' => ['ftp://contoh.test'],
            'teks bebas' => ['belum diisi'],
            'nama variabel saja' => ['APP_URL='],
        ];
    }

    #[DataProvider('bentukTidakDapatDipakai')]
    public function test_bentuk_tidak_dapat_dipakai_jatuh_ke_cadangan(?string $mentah): void
    {
        $this->assertSame(AppUrl::CADANGAN, AppUrl::normalisasi($mentah));
    }

    public function test_cadangan_bukan_url_produksi(): void
    {
        // Pengaman: cadangan harus jelas-jelas lokal, bukan diam-diam
        // menunjuk domain tertentu yang bisa menyamarkan kesalahan.
        $this->assertStringContainsString('localhost', AppUrl::CADANGAN);
    }

    public function test_hasil_tidak_pernah_mengandung_tanda_kutip_atau_nama_variabel(): void
    {
        foreach (self::bentukKotor() as $kasus) {
            $hasil = AppUrl::normalisasi($kasus[0]);

            $this->assertStringNotContainsString('"', $hasil);
            $this->assertStringNotContainsString("'", $hasil);
            $this->assertStringNotContainsString('APP_URL', $hasil);
            $this->assertStringNotContainsString('=', $hasil);
            $this->assertMatchesRegularExpression('#^https?://#', $hasil);
            $this->assertStringEndsNotWith('/', $hasil);
        }
    }
}
