<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMobilRequest;
use App\Http\Requests\UpdateMobilRequest;
use App\Http\Resources\MobilResource;
use App\Http\Responses\ApiResponse;
use App\Models\Mobil;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MobilController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'sort_by' => ['sometimes', Rule::in(['nama', 'brand', 'harga', 'harga_sewa_harian', 'tahun', 'created_at'])],
            'sort_dir' => ['sometimes', Rule::in(['asc', 'desc'])],
            'status' => ['sometimes', Rule::in(['tersedia', 'disewa', 'servis', 'sold'])],
            'transmisi' => ['sometimes', Rule::in(['Manual', 'Matic', 'CVT'])],
            'bahan_bakar' => ['sometimes', Rule::in(['Bensin', 'Diesel', 'Hybrid', 'Listrik'])],
            'tahun' => ['sometimes', 'integer'],
            'min_harga' => ['sometimes', 'integer', 'min:0'],
            'max_harga' => ['sometimes', 'integer', 'min:0'],
            'max_harga_sewa' => ['sometimes', 'integer', 'min:0'],
        ]);

        $mobils = Mobil::query()
            ->filter($request->only([
                'search', 'brand', 'status', 'transmisi', 'bahan_bakar',
                'tahun', 'min_harga', 'max_harga', 'max_harga_sewa',
            ]))
            ->withCount(['penyewaans as penyewaans_aktif_count' => fn ($q) => $q->whereIn('status', ['booked', 'berjalan'])])
            ->orderBy($request->input('sort_by', 'created_at'), $request->input('sort_dir', 'desc'))
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return $this->paginated('Daftar mobil berhasil diambil', $mobils, MobilResource::class);
    }

    public function store(StoreMobilRequest $request): JsonResponse
    {
        $mobil = Mobil::create($request->validated());

        return $this->success('Data mobil berhasil ditambahkan', new MobilResource($mobil), 201);
    }

    public function show(Mobil $mobil): JsonResponse
    {
        $mobil->loadCount(['penyewaans as penyewaans_aktif_count' => fn ($q) => $q->whereIn('status', ['booked', 'berjalan'])]);

        return $this->success('Detail mobil berhasil diambil', new MobilResource($mobil));
    }

    public function update(UpdateMobilRequest $request, Mobil $mobil): JsonResponse
    {
        $mobil->update($request->validated());

        return $this->success('Data mobil berhasil diperbarui', new MobilResource($mobil->fresh()));
    }

    public function destroy(Mobil $mobil): JsonResponse
    {
        try {
            $mobil->delete();
        } catch (QueryException $e) {
            if ($this->adalahPelanggaranForeignKey($e)) {
                return $this->error('Mobil tidak dapat dihapus karena masih memiliki riwayat penyewaan.', 409);
            }

            throw $e;
        }

        return $this->success('Data mobil berhasil dihapus', null);
    }

    /**
     * Unggah foto unit ke storage publik (bonus upload file).
     */
    public function uploadFoto(Request $request, Mobil $mobil): JsonResponse
    {
        $request->validate([
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'foto.required' => 'Berkas foto wajib diunggah.',
            'foto.image' => 'Berkas harus berupa gambar.',
            'foto.mimes' => 'Format foto harus jpg, jpeg, png, atau webp.',
            'foto.max' => 'Ukuran foto maksimal 4 MB.',
        ]);

        if ($mobil->foto && str_starts_with($mobil->foto, '/storage/')) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $mobil->foto));
        }

        $path = $request->file('foto')->store('mobil', 'public');
        $mobil->update(['foto' => Storage::disk('public')->url($path)]);

        return $this->success('Foto mobil berhasil diunggah', new MobilResource($mobil->fresh()));
    }

    private function adalahPelanggaranForeignKey(QueryException $e): bool
    {
        return in_array((string) $e->getCode(), ['23000', '23503'], true);
    }
}
