<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePenyewaanRequest;
use App\Http\Requests\UpdatePenyewaanRequest;
use App\Http\Resources\PenyewaanResource;
use App\Http\Responses\ApiResponse;
use App\Models\Penyewaan;
use App\Services\PenyewaanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PenyewaanController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly PenyewaanService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'sort_by' => ['sometimes', Rule::in(['tanggal_mulai', 'tanggal_selesai', 'total_biaya', 'created_at'])],
            'sort_dir' => ['sometimes', Rule::in(['asc', 'desc'])],
            'status' => ['sometimes', Rule::in(['booked', 'berjalan', 'selesai', 'batal'])],
            'mobil_id' => ['sometimes', 'integer'],
            'pelanggan_id' => ['sometimes', 'integer'],
        ]);

        $penyewaans = Penyewaan::query()
            ->with(['mobil', 'pelanggan'])
            ->filter($request->only([
                'status', 'mobil_id', 'pelanggan_id', 'kode_booking', 'dari', 'sampai', 'search',
            ]))
            ->orderBy($request->input('sort_by', 'created_at'), $request->input('sort_dir', 'desc'))
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return $this->paginated('Daftar penyewaan berhasil diambil', $penyewaans, PenyewaanResource::class);
    }

    public function store(StorePenyewaanRequest $request): JsonResponse
    {
        $penyewaan = $this->service->buat($request->validated());

        return $this->success(
            'Penyewaan berhasil dibuat',
            new PenyewaanResource($penyewaan->load(['mobil', 'pelanggan'])),
            201
        );
    }

    public function show(Penyewaan $penyewaan): JsonResponse
    {
        return $this->success(
            'Detail penyewaan berhasil diambil',
            new PenyewaanResource($penyewaan->load(['mobil', 'pelanggan']))
        );
    }

    public function update(UpdatePenyewaanRequest $request, Penyewaan $penyewaan): JsonResponse
    {
        $penyewaan = $this->service->perbarui($penyewaan, $request->validated());

        return $this->success('Penyewaan berhasil diperbarui', new PenyewaanResource($penyewaan));
    }

    public function destroy(Penyewaan $penyewaan): JsonResponse
    {
        $mobil = $penyewaan->mobil;

        $penyewaan->delete();

        if ($mobil) {
            $this->service->sinkronkanStatusMobil($penyewaan);
        }

        return $this->success('Data penyewaan berhasil dihapus', null);
    }

    /**
     * Kembalikan unit dan hitung denda keterlambatan.
     */
    public function kembalikan(Request $request, Penyewaan $penyewaan): JsonResponse
    {
        $validated = $request->validate([
            'tanggal_dikembalikan' => ['nullable', 'date'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ], [
            'tanggal_dikembalikan.date' => 'Tanggal pengembalian tidak valid.',
        ]);

        $penyewaan = $this->service->kembalikan(
            $penyewaan,
            $validated['tanggal_dikembalikan'] ?? null,
            $validated['catatan'] ?? null
        );

        $pesan = $penyewaan->denda > 0
            ? 'Unit berhasil dikembalikan dengan denda keterlambatan Rp'.number_format($penyewaan->denda, 0, ',', '.')
            : 'Unit berhasil dikembalikan tanpa denda';

        return $this->success($pesan, new PenyewaanResource($penyewaan));
    }

    public function batalkan(Penyewaan $penyewaan): JsonResponse
    {
        $penyewaan = $this->service->batalkan($penyewaan);

        return $this->success('Penyewaan berhasil dibatalkan', new PenyewaanResource($penyewaan));
    }
}
