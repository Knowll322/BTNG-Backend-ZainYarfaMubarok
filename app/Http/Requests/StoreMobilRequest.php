<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMobilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plat_nomor' => ['required', 'string', 'max:15', 'unique:mobils,plat_nomor'],
            'nama' => ['required', 'string', 'max:255'],
            'brand' => ['required', 'string', 'max:50'],
            'harga' => ['required', 'integer', 'min:0'],
            'harga_sewa_harian' => ['required', 'integer', 'min:0'],
            'tahun' => ['required', 'integer', 'between:1900,'.(now()->year + 1)],
            'transmisi' => ['required', Rule::in(['Manual', 'Matic', 'CVT'])],
            'bahan_bakar' => ['required', Rule::in(['Bensin', 'Diesel', 'Hybrid', 'Listrik'])],
            'kapasitas_kursi' => ['required', 'integer', 'between:1,60'],
            'status' => ['sometimes', Rule::in(['tersedia', 'disewa', 'servis', 'sold'])],
            'foto' => ['nullable', 'string', 'max:2048'],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'plat_nomor.required' => 'Kolom plat nomor wajib diisi.',
            'plat_nomor.unique' => 'Plat nomor sudah terdaftar.',
            'nama.required' => 'Kolom nama mobil wajib diisi.',
            'brand.required' => 'Kolom brand wajib diisi.',
            'harga.required' => 'Kolom harga harus berupa angka.',
            'harga.integer' => 'Kolom harga harus berupa angka.',
            'harga.min' => 'Harga tidak boleh negatif.',
            'harga_sewa_harian.required' => 'Kolom harga sewa harian wajib diisi.',
            'harga_sewa_harian.integer' => 'Harga sewa harian harus berupa angka.',
            'tahun.required' => 'Kolom tahun wajib diisi.',
            'tahun.between' => 'Tahun rakit tidak valid.',
            'transmisi.required' => 'Kolom transmisi wajib diisi.',
            'transmisi.in' => 'Transmisi harus salah satu dari: Manual, Matic, CVT.',
            'bahan_bakar.required' => 'Kolom bahan bakar wajib diisi.',
            'bahan_bakar.in' => 'Bahan bakar harus salah satu dari: Bensin, Diesel, Hybrid, Listrik.',
            'kapasitas_kursi.required' => 'Kolom kapasitas kursi wajib diisi.',
            'kapasitas_kursi.between' => 'Kapasitas kursi harus antara 1 sampai 60.',
        ];
    }
}
