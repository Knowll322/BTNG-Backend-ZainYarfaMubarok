<?php

namespace App\Support;

/**
 * Normalisasi nilai APP_URL.
 *
 * Nilai ini dibaca dua kali oleh Laravel: sekali oleh web, sekali oleh
 * console. Pada console, SetRequestForConsole memanggil
 * Request::create(config('app.url')), sehingga satu karakter asing saja
 * sudah cukup menggagalkan seluruh proses build dengan pesan
 * "Invalid URI: Scheme is malformed" atau "Host is malformed" — pesan yang
 * tidak menyebut APP_URL sama sekali.
 *
 * Selain itu nilainya dipakai menyusun URL publik berkas:
 * config('app.url').'/storage'. Nilai yang tidak bersih menghasilkan URL
 * foto yang tidak dapat dibuka.
 *
 * Bentuk kotor yang pernah terjadi di lapangan:
 *
 *   APP_URL="https://app.contoh.test"   <- seluruh baris tersimpan sebagai nilai
 *   "https://app.contoh.test"           <- tanda kutip ikut tersimpan
 *   https://app.contoh.test/            <- garis miring di akhir
 *   https://<domain-anda>               <- placeholder dari panduan
 *
 * Semua bentuk di atas dinormalkan di sini. Bila hasilnya tetap bukan URL
 * http(s) yang sah, dipakai nilai cadangan agar aplikasi masih dapat
 * dibangun dan diperbaiki lewat environment variables.
 */
class AppUrl
{
    /**
     * Nilai cadangan bila APP_URL tidak dapat dipakai.
     */
    public const CADANGAN = 'http://localhost';

    /**
     * Karakter yang dibuang di kedua ujung nilai.
     */
    private const KUTIP = " \t\n\r\0\x0B\"'";

    public static function normalisasi(?string $mentah): string
    {
        $url = trim((string) $mentah, self::KUTIP);

        /*
         * Buang awalan "NAMA_VAR=" bila seluruh baris ikut tersimpan
         * sebagai nilai — misalnya saat "APP_URL=https://..." ditempel ke
         * kolom value milik variabel APP_URL di mode EXPERT Clever Cloud.
         *
         * URL yang sah tidak pernah diawali HURUF_BESAR_BERIKUT_TITIK_DUA,
         * jadi pola ini tidak akan merusak nilai yang benar.
         */
        if (preg_match('/^[A-Z][A-Z0-9_]*\s*=\s*(.*)$/s', $url, $cocok)) {
            $url = trim($cocok[1], self::KUTIP);
        }

        if ($url === ''
            || filter_var($url, FILTER_VALIDATE_URL) === false
            || ! preg_match('#^https?://#i', $url)) {
            return self::CADANGAN;
        }

        return rtrim($url, '/');
    }
}
