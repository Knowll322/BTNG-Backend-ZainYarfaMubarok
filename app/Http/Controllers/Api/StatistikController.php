<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PenyewaanResource;
use App\Http\Responses\ApiResponse;
use App\Models\Mobil;
use App\Models\Pelanggan;
use App\Models\Penyewaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class StatistikController extends Controller
{
    use ApiResponse;

    /**
     * Ringkasan operasional showroom: stok, pelanggan, penyewaan, dan pendapatan.
     */
    public function index(): JsonResponse
    {
        $statusMobil = Mobil::query()
            ->select('status', DB::raw('COUNT(*) as jumlah'))
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        $statusPenyewaan = Penyewaan::query()
            ->select('status', DB::raw('COUNT(*) as jumlah'))
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        $pendapatanSelesai = (int) Penyewaan::query()
            ->where('status', 'selesai')
            ->sum(DB::raw('total_biaya + denda'));

        $pendapatanBulanIni = (int) Penyewaan::query()
            ->where('status', 'selesai')
            ->whereBetween('tanggal_dikembalikan', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->sum(DB::raw('total_biaya + denda'));

        $mobilTerpopuler = Penyewaan::query()
            ->select('mobil_id', DB::raw('COUNT(*) as total_sewa'))
            ->with('mobil:id,nama,brand,plat_nomor')
            ->groupBy('mobil_id')
            ->orderByDesc('total_sewa')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'mobil_id' => $row->mobil_id,
                'nama' => $row->mobil?->nama,
                'brand' => $row->mobil?->brand,
                'plat_nomor' => $row->mobil?->plat_nomor,
                'total_sewa' => (int) $row->total_sewa,
            ]);

        return $this->success('Statistik showroom berhasil diambil', [
            'total_mobil' => (int) $statusMobil->sum(),
            'mobil_per_status' => [
                'tersedia' => (int) ($statusMobil['tersedia'] ?? 0),
                'disewa' => (int) ($statusMobil['disewa'] ?? 0),
                'servis' => (int) ($statusMobil['servis'] ?? 0),
                'sold' => (int) ($statusMobil['sold'] ?? 0),
            ],
            'total_pelanggan' => Pelanggan::count(),
            'total_penyewaan' => (int) $statusPenyewaan->sum(),
            'penyewaan_per_status' => [
                'booked' => (int) ($statusPenyewaan['booked'] ?? 0),
                'berjalan' => (int) ($statusPenyewaan['berjalan'] ?? 0),
                'selesai' => (int) ($statusPenyewaan['selesai'] ?? 0),
                'batal' => (int) ($statusPenyewaan['batal'] ?? 0),
            ],
            'pendapatan_total' => $pendapatanSelesai,
            'pendapatan_bulan_ini' => $pendapatanBulanIni,
            'mobil_terpopuler' => $mobilTerpopuler,
            'penyewaan_terbaru' => PenyewaanResource::collection(
                Penyewaan::with(['mobil', 'pelanggan'])->latest()->limit(5)->get()
            )->resolve(),
        ]);
    }
}
