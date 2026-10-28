# BTNG Showroom & Rental Mobil — REST API

Backend RESTful API untuk showroom dan rental mobil, dibangun dengan **Laravel 12**, **MySQL**, **Laravel Sanctum**, dan **Scramble** untuk dokumentasi interaktif.

Proyek ini adalah Tugas Akhir Kelas Website Backend BTNG.

![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.x-4479A1?logo=mysql&logoColor=white)
![Sanctum](https://img.shields.io/badge/Auth-Sanctum-FF2D20)
![Scramble](https://img.shields.io/badge/Docs-Scramble-4B5563)

---

## Live Demo

| Keterangan | URL |
|---|---|
| Base URL API | `https://app-f4092249-75ca-459c-a6be-782e7a51f20c.cleverapps.io/api` |
| Dokumentasi Interaktif (Scramble) | `https://app-f4092249-75ca-459c-a6be-782e7a51f20c.cleverapps.io/docs/api` |
| OpenAPI JSON | `https://app-f4092249-75ca-459c-a6be-782e7a51f20c.cleverapps.io/docs/api.json` |
| Repository | https://github.com/zainnanann2/BTNG-Backend-ZainYarfaMubarok |

> Aplikasi berjalan di Clever Cloud zona Singapore, dengan add-on MySQL zona Paris. Membuka root `/` akan otomatis diarahkan ke `/docs/api`.

---

## Autentikasi

Rute baca terbuka untuk umum supaya penguji dapat langsung mencoba `GET`
dari `/docs/api` tanpa membuat akun. Seluruh rute tulis wajib token.

| Jenis | Endpoint | Token |
|---|---|---|
| Baca | `GET` pada `/api/mobil`, `/api/pelanggan`, `/api/penyewaan`, `/api/statistik`, `/api/health` | tidak perlu |
| Tulis | `POST`, `PUT`, `PATCH`, `DELETE` | **wajib** `Authorization: Bearer <token>` |
| Akun | `POST /api/auth/register`, `POST /api/auth/login` | tidak perlu |
| Akun | `GET /api/auth/me`, `POST /api/auth/logout` | **wajib** |

Akun hasil seeder:

```
email    : admin@showroom.test
password : password123
```

Tiga langkah menguji endpoint bertoken lewat `/docs/api`:

1. Buka **Auth → Login**, tekan **Send**. Salin nilai `data.token` dari respons.
2. Tekan tombol **Authorize** di kanan atas, tempel token, lalu **Authorize**.
3. Endpoint `POST`/`PUT`/`PATCH`/`DELETE` kini dapat diuji lewat **Try it out**.

Lewat curl:

```bash
TOKEN=$(curl -s -X POST https://app-f4092249-75ca-459c-a6be-782e7a51f20c.cleverapps.io/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@showroom.test","password":"password123"}' | jq -r '.data.token')

curl -X POST https://app-f4092249-75ca-459c-a6be-782e7a51f20c.cleverapps.io/api/mobil \
  -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" -d '{...}'
```

Tanpa token, rute tulis membalas `401` dengan pesan yang menyebut lokasi
endpoint login, sehingga penguji langsung tahu langkah berikutnya.

---

## Fitur

**Wajib (sesuai brief):**

- CRUD lengkap 3 entitas: **Mobil**, **Pelanggan**, **Penyewaan**
- Skema basis data lewat **Migration** (foreign key + index)
- Respons JSON seragam lewat **API Resource** dengan amplop `message` + `data`
- Validasi request memakai **Form Request** / `$request->validate()` dengan pesan Bahasa Indonesia
- Status code semantik: `200`, `201`, `404`, `409`, `422`
- Dokumentasi interaktif **Scramble** di `/docs/api` (bisa *Try it out*)
- **Seeder** data dummy: 10 mobil, 10 pelanggan, 8 penyewaan, 1 akun admin

**Nilai tambah:**

- **Anti-bentrok jadwal** — satu unit tidak bisa disewa pada rentang tanggal yang tumpang tindih
- **Perhitungan otomatis** — `kode_booking`, `durasi_hari` (inklusif), `harga_sewa_per_hari`, dan `total_biaya` dihitung server, bukan dikirim klien
- **Denda keterlambatan** — 50% × harga sewa harian × jumlah hari terlambat, dihitung saat unit dikembalikan
- **Sinkronisasi status unit** — `tersedia` ⇄ `disewa` otomatis mengikuti status penyewaan
- **Pencarian, filter, sorting, pagination** — lengkap dengan `meta` dan `links`
- **Endpoint statistik** — pendapatan, okupansi, mobil terpopuler, penyewaan terbaru
- **Integritas data** — `DELETE` mengembalikan `409` bila data masih punya riwayat penyewaan
- **Otentikasi Sanctum** — `register`, `login`, `me`, `logout` dengan Bearer token
- **Upload foto unit** — disimpan ke Laravel Storage (disk `public`)
- **Error handler global** — `401`, `404`, `405`, `409`, `422` tetap memakai amplop JSON yang sama
- **49 feature test** (209 assertion) untuk CRUD, validasi, logika booking, denda, dan URL foto
- **Koleksi Postman** - 32 request dalam 8 folder, dengan test script dan auto-capture token/ID

---

## Struktur Data

### `mobils`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint | Primary key |
| `plat_nomor` | string(15) | Unik |
| `nama` | string | Model mobil |
| `brand` | string(50) | Merk |
| `harga` | bigint | Harga unit (Rupiah) |
| `harga_sewa_harian` | bigint | Tarif sewa per hari |
| `tahun` | smallint | Tahun rakit |
| `transmisi` | enum | `Manual`, `Matic`, `CVT` |
| `bahan_bakar` | enum | `Bensin`, `Diesel`, `Hybrid`, `Listrik` |
| `kapasitas_kursi` | tinyint | Jumlah kursi |
| `status` | enum | `tersedia`, `disewa`, `servis`, `sold` |
| `foto` | string | URL foto |
| `deskripsi` | text | Opsional |

### `pelanggans`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint | Primary key |
| `nama` | string | Nama lengkap |
| `nik` | char(16) | Unik, 16 digit |
| `email` | string | Unik |
| `telepon` | string(20) | Nomor telepon |
| `alamat` | text | Alamat lengkap |
| `nomor_sim` | string(20) | Unik |
| `foto_ktp` | string | Opsional |

### `penyewaans`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint | Primary key |
| `kode_booking` | string(30) | Unik, format `BK-YYYYMMDD-XXXXX` |
| `mobil_id` | bigint | FK → `mobils` |
| `pelanggan_id` | bigint | FK → `pelanggans` |
| `tanggal_mulai` | date | Tanggal sewa mulai |
| `tanggal_selesai` | date | Tanggal sewa selesai |
| `durasi_hari` | smallint | Dihitung server (inklusif) |
| `harga_sewa_per_hari` | bigint | Snapshot tarif saat booking |
| `total_biaya` | bigint | `durasi_hari × harga_sewa_per_hari` |
| `denda` | bigint | Dihitung saat pengembalian |
| `status` | enum | `booked`, `berjalan`, `selesai`, `batal` |
| `tanggal_dikembalikan` | date | Nullable |
| `catatan` | text | Opsional |

**Relasi:** `Mobil` 1—N `Penyewaan`, `Pelanggan` 1—N `Penyewaan`.

---

## Instalasi Lokal

Prasyarat: **PHP 8.2+**, **Composer**, **MySQL 8** (Laragon/XAMPP).

```bash
# 1. Clone repository
git clone https://github.com/zainnanann2/BTNG-Backend-ZainYarfaMubarok.git
cd BTNG-Backend-ZainYarfaMubarok

# 2. Install dependency
composer install

# 3. Salin file environment
copy .env.example .env      # Windows
# cp .env.example .env      # Linux/macOS

# 4. Generate application key
php artisan key:generate

# 5. Buat database MySQL bernama btng_showroom
#    (lewat phpMyAdmin, Laragon, atau CLI)
#    mysql -u root -e "CREATE DATABASE btng_showroom CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 6. Sesuaikan kredensial database di .env
#    DB_HOST=127.0.0.1
#    DB_PORT=3306
#    DB_DATABASE=btng_showroom
#    DB_USERNAME=root
#    DB_PASSWORD=

# 7. Migrasi + isi data dummy
php artisan migrate --seed

# 8. Tautkan storage publik (untuk upload foto)
php artisan storage:link

# 9. Jalankan server
php artisan serve
```

Buka:

- Dokumentasi interaktif → http://127.0.0.1:8000/docs/api
- Base API → http://127.0.0.1:8000/api

---

## Akun Demo

Dibuat otomatis oleh `UserSeeder`:

| Field | Nilai |
|---|---|
| Email | `admin@showroom.test` |
| Password | `password123` |

```bash
curl -X POST http://127.0.0.1:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"email\":\"admin@showroom.test\",\"password\":\"password123\"}"
```

---

## Daftar Endpoint

Semua endpoint berada di bawah prefix `/api`. Respons memakai amplop:

```json
{ "message": "...", "data": ... }
```

Koleksi terpaginasi menambahkan `meta` dan `links`.

### Health

| Method | Endpoint | Deskripsi | Status |
|---|---|---|---|
| GET | `/api/health` | Cek status API | 200 |

### Mobil

| Method | Endpoint | Deskripsi | Token | Status |
|---|---|---|---|---|
| GET | `/api/mobil` | Daftar mobil (filter, search, sort, pagination) | — | 200 |
| GET | `/api/mobil/{id}` | Detail mobil | — | 200 |
| POST | `/api/mobil` | Tambah mobil | wajib | 201 |
| PUT/PATCH | `/api/mobil/{id}` | Perbarui mobil | wajib | 200 |
| DELETE | `/api/mobil/{id}` | Hapus mobil | wajib | 200 / 409 |
| POST | `/api/mobil/{id}/foto` | Upload foto unit (multipart) | wajib | 200 |

### Pelanggan

| Method | Endpoint | Deskripsi | Token | Status |
|---|---|---|---|---|
| GET | `/api/pelanggan` | Daftar pelanggan | — | 200 |
| GET | `/api/pelanggan/{id}` | Detail pelanggan | — | 200 |
| POST | `/api/pelanggan` | Tambah pelanggan | wajib | 201 |
| PUT/PATCH | `/api/pelanggan/{id}` | Perbarui pelanggan | wajib | 200 |
| DELETE | `/api/pelanggan/{id}` | Hapus pelanggan | wajib | 200 / 409 |

### Penyewaan

| Method | Endpoint | Deskripsi | Token | Status |
|---|---|---|---|---|
| GET | `/api/penyewaan` | Daftar penyewaan | — | 200 |
| GET | `/api/penyewaan/{id}` | Detail penyewaan | — | 200 |
| POST | `/api/penyewaan` | Buat penyewaan (anti-bentrok) | wajib | 201 |
| PUT/PATCH | `/api/penyewaan/{id}` | Perbarui penyewaan | wajib | 200 |
| DELETE | `/api/penyewaan/{id}` | Hapus penyewaan | wajib | 200 |
| POST | `/api/penyewaan/{id}/kembalikan` | Kembalikan unit + hitung denda | wajib | 200 |
| POST | `/api/penyewaan/{id}/batalkan` | Batalkan penyewaan | wajib | 200 |

### Statistik

| Method | Endpoint | Deskripsi | Status |
|---|---|---|---|
| GET | `/api/statistik` | Ringkasan operasional showroom | 200 |

### Autentikasi

| Method | Endpoint | Deskripsi | Status |
|---|---|---|---|
| POST | `/api/auth/register` | Registrasi akun | 201 |
| POST | `/api/auth/login` | Login, terbitkan token | 200 |
| GET | `/api/auth/me` | Profil pengguna (Bearer) | 200 |
| POST | `/api/auth/logout` | Cabut token (Bearer) | 200 |

---

## Contoh Request & Response

### 1. Ambil daftar mobil (200 OK)

```
GET /api/mobil?brand=Toyota&status=tersedia&per_page=5
```

```json
{
  "message": "Daftar mobil berhasil diambil",
  "data": [
    {
      "id": 1,
      "plat_nomor": "B 1024 TZA",
      "nama": "Toyota Avanza 1.5 G CVT",
      "brand": "Toyota",
      "harga": 275000000,
      "harga_sewa_harian": 350000,
      "tahun": 2023,
      "transmisi": "CVT",
      "bahan_bakar": "Bensin",
      "kapasitas_kursi": 7,
      "status": "tersedia",
      "foto": "https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=1200",
      "deskripsi": "MPV keluarga paling dicari, kabin lega, konsumsi BBM irit.",
      "penyewaan_aktif": 0,
      "created_at": "2026-10-27T13:18:49+00:00",
      "updated_at": "2026-10-27T13:18:49+00:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 5,
    "total": 2,
    "last_page": 1,
    "from": 1,
    "to": 2
  },
  "links": {
    "first": "http://127.0.0.1:8000/api/mobil?page=1",
    "prev": null,
    "next": null,
    "last": "http://127.0.0.1:8000/api/mobil?page=1"
  }
}
```

### 2. Tambah mobil (201 Created)

```json
{
  "plat_nomor": "B 1234 XYZ",
  "nama": "Toyota Avanza 1.5 G CVT",
  "brand": "Toyota",
  "harga": 275000000,
  "harga_sewa_harian": 350000,
  "tahun": 2024,
  "transmisi": "CVT",
  "bahan_bakar": "Bensin",
  "kapasitas_kursi": 7
}
```

```json
{
  "message": "Data mobil berhasil ditambahkan",
  "data": {
    "id": 11,
    "plat_nomor": "B 1234 XYZ",
    "nama": "Toyota Avanza 1.5 G CVT",
    "status": "tersedia"
  }
}
```

### 3. Gagal validasi (422 Unprocessable Content)

```json
{
  "plat_nomor": "",
  "harga": "mahal",
  "transmisi": "Otomatis"
}
```

```json
{
  "message": "Kolom plat nomor wajib diisi. (and 6 more error)",
  "data": null,
  "errors": {
    "plat_nomor": ["Kolom plat nomor wajib diisi."],
    "nama": ["Kolom nama mobil wajib diisi."],
    "brand": ["Kolom brand wajib diisi."],
    "harga": ["Kolom harga harus berupa angka."],
    "transmisi": ["Transmisi harus salah satu dari: Manual, Matic, CVT."]
  }
}
```

### 4. Buat penyewaan (201 Created)

```json
{
  "mobil_id": 1,
  "pelanggan_id": 1,
  "tanggal_mulai": "2026-11-01",
  "tanggal_selesai": "2026-11-03"
}
```

```json
{
  "message": "Penyewaan berhasil dibuat",
  "data": {
    "id": 9,
    "kode_booking": "BK-20261101-A7K2M",
    "mobil_id": 1,
    "pelanggan_id": 1,
    "tanggal_mulai": "2026-11-01",
    "tanggal_selesai": "2026-11-03",
    "durasi_hari": 3,
    "harga_sewa_per_hari": 350000,
    "total_biaya": 1050000,
    "denda": 0,
    "status": "booked"
  }
}
```

Bila tanggal bertumpuk dengan penyewaan lain pada unit yang sama → **422**:

```json
{
  "message": "Mobil sudah disewa pada rentang tanggal tersebut.",
  "data": null,
  "errors": {
    "tanggal_mulai": ["Mobil sudah disewa pada rentang tanggal tersebut."]
  }
}
```

### 5. Kembalikan unit dengan denda (200 OK)

```
POST /api/penyewaan/9/kembalikan
```

```json
{
  "tanggal_dikembalikan": "2026-11-05"
}
```

```json
{
  "message": "Unit berhasil dikembalikan dengan denda keterlambatan Rp350.000",
  "data": {
    "id": 9,
    "status": "selesai",
    "tanggal_dikembalikan": "2026-11-05",
    "total_biaya": 1050000,
    "denda": 350000
  }
}
```

Denda = 2 hari terlambat × 50% × Rp350.000 = Rp350.000.

---

## Query Parameter

### `GET /api/mobil`

| Parameter | Contoh | Keterangan |
|---|---|---|
| `search` | `?search=avanza` | Cari di `nama`, `brand`, `plat_nomor` |
| `brand` | `?brand=Toyota` | Filter merk |
| `status` | `?status=tersedia` | `tersedia`, `disewa`, `servis`, `sold` |
| `transmisi` | `?transmisi=CVT` | `Manual`, `Matic`, `CVT` |
| `bahan_bakar` | `?bahan_bakar=Listrik` | `Bensin`, `Diesel`, `Hybrid`, `Listrik` |
| `tahun` | `?tahun=2024` | Filter tahun rakit |
| `min_harga` | `?min_harga=200000000` | Harga minimum |
| `max_harga` | `?max_harga=500000000` | Harga maksimum |
| `max_harga_sewa` | `?max_harga_sewa=400000` | Tarif sewa harian maksimum |
| `sort_by` | `?sort_by=harga` | `nama`, `brand`, `harga`, `harga_sewa_harian`, `tahun`, `created_at` |
| `sort_dir` | `?sort_dir=asc` | `asc` / `desc` |
| `per_page` | `?per_page=20` | 1–100, default 10 |

### `GET /api/pelanggan`

| Parameter | Contoh | Keterangan |
|---|---|---|
| `search` | `?search=zain` | Cari di `nama`, `email`, `nik`, `telepon` |
| `email` | `?email=a@b.com` | Filter email persis |
| `sort_by` | `?sort_by=nama` | `nama`, `email`, `created_at` |
| `per_page` | `?per_page=20` | 1–100 |

### `GET /api/penyewaan`

| Parameter | Contoh | Keterangan |
|---|---|---|
| `status` | `?status=berjalan` | `booked`, `berjalan`, `selesai`, `batal` |
| `mobil_id` | `?mobil_id=1` | Filter unit |
| `pelanggan_id` | `?pelanggan_id=1` | Filter pelanggan |
| `kode_booking` | `?kode_booking=BK-20261101-A7K2M` | Filter kode |
| `dari` | `?dari=2026-11-01` | `tanggal_mulai` ≥ nilai |
| `sampai` | `?sampai=2026-11-30` | `tanggal_selesai` ≤ nilai |
| `search` | `?search=avanza` | Cari di kode, nama mobil, nama pelanggan |
| `sort_by` | `?sort_by=total_biaya` | `tanggal_mulai`, `tanggal_selesai`, `total_biaya`, `created_at` |
| `per_page` | `?per_page=20` | 1–100 |

---

## Dokumentasi API

Dokumentasi interaktif dihasilkan otomatis oleh **Scramble** dari route, Form Request, dan API Resource — tidak perlu menulis anotasi manual.

| URL | Isi |
|---|---|
| `/docs/api` | UI interaktif (Stoplight Elements) dengan tombol **Try it out** |
| `/docs/api.json` | Spesifikasi OpenAPI 3.1 |

Akses dibuka publik melalui gate `viewApiDocs` di `app/Providers/AppServiceProvider.php`:

```php
Gate::define('viewApiDocs', fn ($user = null) => true);
```

---

## Koleksi Postman

Tersedia di [`postman/BTNG-Backend-ZainYarfaMubarok.postman_collection.json`](postman/BTNG-Backend-ZainYarfaMubarok.postman_collection.json) — **32 request** dalam 8 folder.

| Folder | Isi |
|---|---|
| Health | Cek status API |
| Auth | Register, Login, Profil saya |
| Mobil | Daftar, Detail, Tambah, Perbarui, Upload foto, Hapus, Gagal validasi (422) |
| Pelanggan | Daftar, Detail, Tambah, Perbarui, Hapus, Gagal validasi (422) |
| Penyewaan | Daftar, Detail, Buat, Perbarui, Kembalikan, Batalkan, Hapus, Gagal bentrok (422) |
| Statistik | Ringkasan dashboard |
| Error Handling | 404 endpoint tidak ada, 401 tanpa token, 401 tulis tanpa token |
| Logout (terakhir) | Mencabut token |

Cara pakai:

1. Import file JSON ke Postman.
2. Sesuaikan variabel `baseUrl` — `http://127.0.0.1:8000` untuk lokal, atau URL Clever Cloud.
3. Jalankan **Auth → Login**. Token tersimpan otomatis ke variabel `token` dan dipakai semua request lewat auth Bearer koleksi.
4. Jalankan **Mobil → Tambah mobil** dan **Pelanggan → Tambah pelanggan**. ID hasil simpan tersimpan otomatis ke `mobilId` / `pelangganId`.
5. Jalankan **Penyewaan → Buat penyewaan**. Pre-request script mengisi `tanggalMulai` (+7 hari) dan `tanggalSelesai` (+9 hari) secara otomatis.
6. **Logout (terakhir)** dijalankan paling akhir — ia mencabut token, sehingga request sesudahnya akan ditolak 401. Untuk mengulang, jalankan koleksi dari awal.

Setiap request punya test script yang memeriksa status code, amplop `message` + `data`, dan aturan bisnis (format `kode_booking`, `total_biaya = durasi_hari × harga_sewa_per_hari`).

> Koleksi ini membuat data baru saat dijalankan. Setelah dipakai terhadap server live,
> hapus sisa uji agar data kembali sesuai seeder — mobil uji berciri plat berakhiran
> ` TST`, penyewaan uji berciri `kode_booking` bersufiks alfanumerik acak (seeder
> selalu memakai lima angka, mis. `BK-20261028-00001`).

---

## Deploy ke Clever Cloud

1. Buat aplikasi **PHP** di Clever Cloud, hubungkan repo GitHub ini (branch `main`).
2. Tambahkan add-on **MySQL** dan tautkan (*link*) ke aplikasi.
3. Buka **Environment variables** → mode **EXPERT**, isi:

```
CC_WEBROOT=/public
CC_PHP_VERSION=8.3
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...
APP_URL=https://app-f4092249-75ca-459c-a6be-782e7a51f20c.cleverapps.io
LOG_CHANNEL=stderr
DB_CONNECTION=mysql
CC_PRE_RUN_HOOK=php artisan storage:link && php artisan config:clear && php artisan migrate --force && php artisan db:seed --force
```

Nilai di atas **tanpa tanda kutip**. Clever Cloud mode EXPERT menyimpan nilai
apa adanya, jadi `APP_URL="https://..."` menyimpan tanda kutipnya secara
literal. Aplikasi tetap dapat dibangun — `config/app.php` menormalkan nilai
itu — tetapi `config/filesystems.php` dahulu membaca `env('APP_URL')` mentah
sehingga URL foto menjadi
`APP_URL="https://..."/storage/mobil/xxxx.png` dan tidak dapat dibuka.
Pembacaan sekarang memakai `config('app.url')`, dan `FotoUrlTest` menjaga agar
tidak kembali. Tetap tulis tanpa tanda kutip.

Baris `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD`
**tidak perlu diisi**. Clever Cloud menyuntik kredensial dengan nama
`MYSQL_ADDON_*`, dan `config/database.php` sudah membaca nama itu sebagai
cadangan. Menulis `DB_*` secara manual justru berisiko menimpa nilai add-on.

4. Klik **Update changes**, lalu **RE-BUILD AND START** (bukan sekadar START —
   build diperlukan agar commit terbaru terpakai).
5. Pantau tab **Logs** sampai Composer selesai, migrasi berjalan, dan Apache/PHP siap.
6. Buka `https://app-f4092249-75ca-459c-a6be-782e7a51f20c.cleverapps.io/docs/api`.

**Catatan penting:**

- `CC_WEBROOT=/public` wajib. Tanpa ini Clever Cloud membaca root proyek dan muncul **403 Forbidden**.
- `APP_URL` harus berupa URL sungguhan saat build berjalan. Placeholder seperti
  `https://<domain-anda>` membuat `php artisan package:discover` gagal dengan
  `Invalid URI: Host is malformed`, dan seluruh deploy ditandai **Failed**
  dalam hitungan detik. Nilai ini dibaca saat build, bukan hanya saat runtime.
- `CC_PHP_VERSION` sebaiknya `8.3` atau lebih baru; PHP 8.2 mencapai
  end-of-life pada 31 Desember 2026.
- `CC_PRE_RUN_HOOK` menjalankan migrasi dan seeder setiap aplikasi start.
  Keduanya idempoten, jadi aman diulang. Bila hook gagal, deploy ikut gagal —
  periksa log.
- `APP_KEY` harus diisi, kalau tidak semua request menghasilkan **500**.
- Add-on dan aplikasi boleh berada di zona berbeda (di sini aplikasi Singapore,
  database Paris). Yang tidak bisa lintas zona hanya FSBucket.

---

## Pengujian

```bash
php artisan test
```

Hasil saat ini: **49 passed, 209 assertions**.

Cakupan: amplop respons, validasi 422, 404 untuk data tidak ada, 409 untuk relasi FK, filter/pencarian/pagination, perhitungan durasi dan total, anti-bentrok jadwal, denda keterlambatan, sinkronisasi status unit, serta autentikasi token.

Koleksi Postman juga dijalankan langsung terhadap server produksi di Clever Cloud:

```bash
postman collection run postman/BTNG-Backend-ZainYarfaMubarok.postman_collection.json \
  --env-var "baseUrl=https://app-f4092249-75ca-459c-a6be-782e7a51f20c.cleverapps.io"
```

Hasil: **32 request, 122 assertion, 0 gagal**, rata-rata 3 detik per request.

Request `Logout` sengaja diletakkan di folder terakhir karena ia mencabut
token. Bila dijalankan lebih awal, seluruh request tulis sesudahnya akan
mengirim token kosong dan berakhir 401.

---

## Catatan upload foto

Endpoint `POST /api/mobil/{id}/foto` menyimpan berkas ke disk `public`,
sehingga memerlukan symlink `public/storage` → `storage/app/public`. Di
Clever Cloud symlink itu tidak ikut tersimpan di repositori, jadi dibuat
otomatis saat aplikasi start melalui `CC_PRE_RUN_HOOK`:

```
php artisan storage:link && php artisan config:clear && php artisan migrate --force && php artisan db:seed --force
```

Tanpa `storage:link`, unggahan berhasil tetapi URL foto akan mengarah ke
berkas yang tidak ada (`404`). Saat menjalankan di lokal, jalankan
`php artisan storage:link` sekali setelah `composer install`.

---

## Lisensi

Dirilis di bawah [MIT License](https://opensource.org/licenses/MIT).
