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
 * Riwayat bentuk kotor yang benar-benar terjadi di Clever Cloud:
 *
 *   APP_URL="https://app.contoh.test"   <- seluruh baris tersimpan sebagai nilai
 *   "https://app.contoh.test"           <- tanda kutip ikut tersimpan
 *   https://app.contoh.test/            <- garis miring di akhir
 *   https://<domain-anda>               <- placeholder dari panduan
 *
 * Menangani satu per satu bentuk itu ternyata rapuh: perbaikan pertama
 * hanya membuang tanda kutip, sehingga bentuk "baris utuh" tetap gagal dan
 * diam-diam jatuh ke cadangan — URL foto menjadi http://localhost/... yang
 * tampak wajar sehingga lolos pemeriksaan sekilas.
 *
 * Karena itu pendekatannya diganti: ambil URL http(s) pertama yang benar-benar
 * ada di dalam nilai, apa pun bentuk pembungkusnya. Satu aturan menangani
 * semua bentuk di atas sekaligus, termasuk bentuk baru yang belum terpikirkan.
 */
class AppUrl
{
    /**
     * Nilai cadangan bila APP_URL tidak memuat URL yang dapat dipakai.
     *
     * Sengaja jelas-jelas lokal agar kesalahan konfigurasi tidak tersamarkan
     * menjadi domain yang terlihat sah.
     */
    public const CADANGAN = 'http://localhost';

    public static function normalisasi(?string $mentah): string
    {
        $mentah = (string) $mentah;

        /*
         * Buang karakter tak terlihat yang kerap ikut tersalin dari browser
         * atau dokumen: spasi tak terputus, spasi lebar nol, penanda urutan
         * byte. Tanpa ini, URL yang tampak benar tetap gagal validasi.
         */
        $mentah = preg_replace('/[\x{00A0}\x{200B}-\x{200D}\x{FEFF}]/u', ' ', $mentah) ?? $mentah;

        /*
         * Ambil URL pertama yang berbentuk http(s).
         *
         * Karakter yang mengakhiri URL: spasi, tanda kutip, kurung sudut
         * (placeholder panduan seperti https://<domain-anda>), dan garis
         * miring terbalik dari escape.
         */
        if (preg_match('#https?://[^\s"\'<>\\\\]+#i', $mentah, $cocok)) {
            $url = rtrim($cocok[0], '/');

            if (filter_var($url, FILTER_VALIDATE_URL) !== false) {
                return $url;
            }
        }

        return self::CADANGAN;
    }
}
