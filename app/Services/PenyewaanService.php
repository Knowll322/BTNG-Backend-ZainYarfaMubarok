<?php

namespace App\Services;

use App\Models\Mobil;
use App\Models\Penyewaan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PenyewaanService
{
    /**
     * Persentase denda keterlambatan dari harga sewa harian.
     */
    public const DENDA_PERSEN_PER_HARI = 0.5;

    /**
     * Buat penyewaan baru: validasi ketersediaan, hitung biaya, kunci unit bila berjalan.
     *
     * @throws ValidationException
     */
    public function buat(array $data): Penyewaan
    {
        $mobil = Mobil::findOrFail($data['mobil_id']);
        $mulai = Carbon::parse($data['tanggal_mulai']);
        $selesai = Carbon::parse($data['tanggal_selesai']);
        $status = $data['status'] ?? 'booked';

        if ($selesai->lessThanOrEqualTo($mulai)) {
            throw ValidationException::withMessages([
                'tanggal_selesai' => ['Tanggal selesai harus setelah tanggal mulai.'],
            ]);
        }

        if (in_array($mobil->status, ['servis', 'sold'], true)) {
            throw ValidationException::withMessages([
                'mobil_id' => ["Mobil {$mobil->nama} sedang {$mobil->status} dan tidak dapat disewa."],
            ]);
        }

        if ($this->adaKonflik($mobil->id, $mulai, $selesai)) {
            throw ValidationException::withMessages([
                'tanggal_mulai' => ['Mobil sudah disewa pada rentang tanggal tersebut.'],
            ]);
        }

        if ($status === 'berjalan' && $this->sedangBerjalan($mobil->id)) {
            throw ValidationException::withMessages([
                'mobil_id' => ['Mobil sedang digunakan pada penyewaan lain.'],
            ]);
        }

        $durasi = $this->hitungDurasi($mulai, $selesai);

        return DB::transaction(function () use ($data, $mobil, $mulai, $selesai, $status, $durasi) {
            $penyewaan = Penyewaan::create([
                'kode_booking' => $this->buatKodeBooking(),
                'mobil_id' => $mobil->id,
                'pelanggan_id' => $data['pelanggan_id'],
                'tanggal_mulai' => $mulai->toDateString(),
                'tanggal_selesai' => $selesai->toDateString(),
                'durasi_hari' => $durasi,
                'harga_sewa_per_hari' => $mobil->harga_sewa_harian,
                'total_biaya' => $durasi * $mobil->harga_sewa_harian,
                'denda' => 0,
                'status' => $status,
                'catatan' => $data['catatan'] ?? null,
            ]);

            if ($status === 'berjalan') {
                $mobil->update(['status' => 'disewa']);
            }

            return $penyewaan;
        });
    }

    /**
     * Perbarui penyewaan: hitung ulang biaya bila tanggal/mobil berubah, jaga transisi status.
     *
     * @throws ValidationException
     */
    public function perbarui(Penyewaan $penyewaan, array $data): Penyewaan
    {
        $mobilId = $data['mobil_id'] ?? $penyewaan->mobil_id;
        $mobil = Mobil::findOrFail($mobilId);
        $mulai = Carbon::parse($data['tanggal_mulai'] ?? $penyewaan->tanggal_mulai);
        $selesai = Carbon::parse($data['tanggal_selesai'] ?? $penyewaan->tanggal_selesai);
        $status = $data['status'] ?? $penyewaan->status;

        if ($selesai->lessThanOrEqualTo($mulai)) {
            throw ValidationException::withMessages([
                'tanggal_selesai' => ['Tanggal selesai harus setelah tanggal mulai.'],
            ]);
        }

        if (in_array($status, ['booked', 'berjalan'], true) && $this->adaKonflik($mobil->id, $mulai, $selesai, $penyewaan->id)) {
            throw ValidationException::withMessages([
                'tanggal_mulai' => ['Mobil sudah disewa pada rentang tanggal tersebut.'],
            ]);
        }

        if ($status === 'berjalan' && $this->sedangBerjalan($mobil->id, $penyewaan->id)) {
            throw ValidationException::withMessages([
                'mobil_id' => ['Mobil sedang digunakan pada penyewaan lain.'],
            ]);
        }

        $durasi = $this->hitungDurasi($mulai, $selesai);
        $hargaPerHari = (int) ($data['harga_sewa_per_hari'] ?? $penyewaan->harga_sewa_per_hari);
        if ($mobil->id !== $penyewaan->mobil_id) {
            $hargaPerHari = $mobil->harga_sewa_harian;
        }

        return DB::transaction(function () use ($penyewaan, $data, $mobil, $mulai, $selesai, $status, $durasi, $hargaPerHari) {
            $penyewaan->fill([
                'mobil_id' => $mobil->id,
                'pelanggan_id' => $data['pelanggan_id'] ?? $penyewaan->pelanggan_id,
                'tanggal_mulai' => $mulai->toDateString(),
                'tanggal_selesai' => $selesai->toDateString(),
                'durasi_hari' => $durasi,
                'harga_sewa_per_hari' => $hargaPerHari,
                'total_biaya' => $durasi * $hargaPerHari,
                'status' => $status,
                'tanggal_dikembalikan' => $data['tanggal_dikembalikan'] ?? $penyewaan->tanggal_dikembalikan,
                'denda' => $data['denda'] ?? $penyewaan->denda,
                'catatan' => $data['catatan'] ?? $penyewaan->catatan,
            ])->save();

            $this->sinkronkanStatusMobil($penyewaan->fresh());

            return $penyewaan->fresh(['mobil', 'pelanggan']);
        });
    }

    /**
     * Kembalikan unit: hitung denda keterlambatan, tutup penyewaan, lepas unit.
     */
    public function kembalikan(Penyewaan $penyewaan, ?string $tanggalDikembalikan = null, ?string $catatan = null): Penyewaan
    {
        if ($penyewaan->status === 'selesai') {
            throw ValidationException::withMessages([
                'status' => ['Penyewaan ini sudah dikembalikan sebelumnya.'],
            ]);
        }

        if ($penyewaan->status === 'batal') {
            throw ValidationException::withMessages([
                'status' => ['Penyewaan yang dibatalkan tidak dapat dikembalikan.'],
            ]);
        }

        $dikembalikan = Carbon::parse($tanggalDikembalikan ?? now()->toDateString())->startOfDay();
        $tenggat = Carbon::parse($penyewaan->tanggal_selesai)->startOfDay();
        $terlambat = max(0, $tenggat->diffInDays($dikembalikan, false));
        $denda = (int) round($terlambat * $penyewaan->harga_sewa_per_hari * self::DENDA_PERSEN_PER_HARI);

        return DB::transaction(function () use ($penyewaan, $dikembalikan, $catatan, $denda) {
            $penyewaan->update([
                'status' => 'selesai',
                'tanggal_dikembalikan' => $dikembalikan->toDateString(),
                'denda' => $denda,
                'catatan' => $catatan ?? $penyewaan->catatan,
            ]);

            $this->sinkronkanStatusMobil($penyewaan->fresh());

            return $penyewaan->fresh(['mobil', 'pelanggan']);
        });
    }

    public function batalkan(Penyewaan $penyewaan): Penyewaan
    {
        if ($penyewaan->status === 'selesai') {
            throw ValidationException::withMessages([
                'status' => ['Penyewaan yang sudah selesai tidak dapat dibatalkan.'],
            ]);
        }

        return DB::transaction(function () use ($penyewaan) {
            $penyewaan->update(['status' => 'batal']);
            $this->sinkronkanStatusMobil($penyewaan->fresh());

            return $penyewaan->fresh(['mobil', 'pelanggan']);
        });
    }

    /**
     * Cek bentrok jadwal pada unit yang sama (tanggal inklusif).
     */
    public function adaKonflik(int $mobilId, Carbon $mulai, Carbon $selesai, ?int $kecualiId = null): bool
    {
        return Penyewaan::query()
            ->where('mobil_id', $mobilId)
            ->whereIn('status', ['booked', 'berjalan'])
            ->when($kecualiId, fn ($q) => $q->whereKeyNot($kecualiId))
            ->whereDate('tanggal_mulai', '<=', $selesai->toDateString())
            ->whereDate('tanggal_selesai', '>=', $mulai->toDateString())
            ->exists();
    }

    public function sedangBerjalan(int $mobilId, ?int $kecualiId = null): bool
    {
        return Penyewaan::query()
            ->where('mobil_id', $mobilId)
            ->where('status', 'berjalan')
            ->when($kecualiId, fn ($q) => $q->whereKeyNot($kecualiId))
            ->exists();
    }

    /**
     * Jumlah hari sewa (inklusif: 1–3 Januari = 3 hari).
     */
    public function hitungDurasi(Carbon $mulai, Carbon $selesai): int
    {
        return (int) $mulai->copy()->startOfDay()->diffInDays($selesai->copy()->startOfDay()) + 1;
    }

    /**
     * Selaraskan status unit dengan penyewaan yang masih aktif.
     */
    public function sinkronkanStatusMobil(Penyewaan $penyewaan): void
    {
        $mobil = $penyewaan->mobil;

        if (! $mobil || in_array($mobil->status, ['servis', 'sold'], true)) {
            return;
        }

        $berjalan = Penyewaan::query()
            ->where('mobil_id', $mobil->id)
            ->where('status', 'berjalan')
            ->exists();

        $mobil->update(['status' => $berjalan ? 'disewa' : 'tersedia']);
    }

    public function buatKodeBooking(): string
    {
        do {
            $kode = 'BK-'.now()->format('Ymd').'-'.strtoupper(Str::random(5));
        } while (Penyewaan::where('kode_booking', $kode)->exists());

        return $kode;
    }
}
