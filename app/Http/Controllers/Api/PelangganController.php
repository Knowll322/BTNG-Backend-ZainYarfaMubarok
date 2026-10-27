<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePelangganRequest;
use App\Http\Requests\UpdatePelangganRequest;
use App\Http\Resources\PelangganResource;
use App\Http\Responses\ApiResponse;
use App\Models\Pelanggan;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PelangganController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'sort_by' => ['sometimes', Rule::in(['nama', 'email', 'created_at'])],
            'sort_dir' => ['sometimes', Rule::in(['asc', 'desc'])],
        ]);

        $pelanggans = Pelanggan::query()
            ->filter($request->only(['search', 'email']))
            ->withCount('penyewaans')
            ->orderBy($request->input('sort_by', 'created_at'), $request->input('sort_dir', 'desc'))
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return $this->paginated('Daftar pelanggan berhasil diambil', $pelanggans, PelangganResource::class);
    }

    public function store(StorePelangganRequest $request): JsonResponse
    {
        $pelanggan = Pelanggan::create($request->validated());

        return $this->success('Data pelanggan berhasil ditambahkan', new PelangganResource($pelanggan), 201);
    }

    public function show(Pelanggan $pelanggan): JsonResponse
    {
        $pelanggan->loadCount('penyewaans');

        return $this->success('Detail pelanggan berhasil diambil', new PelangganResource($pelanggan));
    }

    public function update(UpdatePelangganRequest $request, Pelanggan $pelanggan): JsonResponse
    {
        $pelanggan->update($request->validated());

        return $this->success('Data pelanggan berhasil diperbarui', new PelangganResource($pelanggan->fresh()));
    }

    public function destroy(Pelanggan $pelanggan): JsonResponse
    {
        try {
            $pelanggan->delete();
        } catch (QueryException $e) {
            if (in_array((string) $e->getCode(), ['23000', '23503'], true)) {
                return $this->error('Pelanggan tidak dapat dihapus karena masih memiliki riwayat penyewaan.', 409);
            }

            throw $e;
        }

        return $this->success('Data pelanggan berhasil dihapus', null);
    }
}
