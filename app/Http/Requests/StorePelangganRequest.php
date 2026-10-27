<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePelangganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'digits:16', 'unique:pelanggans,nik'],
            'email' => ['required', 'email', 'max:255', 'unique:pelanggans,email'],
            'telepon' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'alamat' => ['required', 'string', 'max:1000'],
            'nomor_sim' => ['required', 'string', 'max:20', 'unique:pelanggans,nomor_sim'],
            'foto_ktp' => ['nullable', 'string', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Kolom nama pelanggan wajib diisi.',
            'nik.required' => 'Kolom NIK wajib diisi.',
            'nik.digits' => 'NIK harus terdiri dari 16 angka.',
            'nik.unique' => 'NIK sudah terdaftar.',
            'email.required' => 'Kolom email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'telepon.required' => 'Kolom telepon wajib diisi.',
            'telepon.regex' => 'Nomor telepon hanya boleh berisi angka, spasi, +, -, dan tanda kurung.',
            'alamat.required' => 'Kolom alamat wajib diisi.',
            'nomor_sim.required' => 'Kolom nomor SIM wajib diisi.',
            'nomor_sim.unique' => 'Nomor SIM sudah terdaftar.',
        ];
    }
}
