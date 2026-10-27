<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMobilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $mobil = $this->route('mobil');

        return [
            'plat_nomor' => ['sometimes', 'required', 'string', 'max:15', Rule::unique('mobils', 'plat_nomor')->ignore($mobil?->id)],
            'nama' => ['sometimes', 'required', 'string', 'max:255'],
            'brand' => ['sometimes', 'required', 'string', 'max:50'],
            'harga' => ['sometimes', 'required', 'integer', 'min:0'],
            'harga_sewa_harian' => ['sometimes', 'required', 'integer', 'min:0'],
            'tahun' => ['sometimes', 'required', 'integer', 'between:1900,'.(now()->year + 1)],
            'transmisi' => ['sometimes', 'required', Rule::in(['Manual', 'Matic', 'CVT'])],
            'bahan_bakar' => ['sometimes', 'required', Rule::in(['Bensin', 'Diesel', 'Hybrid', 'Listrik'])],
            'kapasitas_kursi' => ['sometimes', 'required', 'integer', 'between:1,60'],
            'status' => ['sometimes', 'required', Rule::in(['tersedia', 'disewa', 'servis', 'sold'])],
            'foto' => ['nullable', 'string', 'max:2048'],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'plat_nomor.unique' => 'Plat nomor sudah terdaftar.',
            'harga.integer' => 'Kolom harga harus berupa angka.',
            'transmisi.in' => 'Transmisi harus salah satu dari: Manual, Matic, CVT.',
            'bahan_bakar.in' => 'Bahan bakar harus salah satu dari: Bensin, Diesel, Hybrid, Listrik.',
        ];
    }
}
