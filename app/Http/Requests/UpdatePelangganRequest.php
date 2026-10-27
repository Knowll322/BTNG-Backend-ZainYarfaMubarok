<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePelangganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $pelanggan = $this->route('pelanggan');

        return [
            'nama' => ['sometimes', 'required', 'string', 'max:255'],
            'nik' => ['sometimes', 'required', 'digits:16', Rule::unique('pelanggans', 'nik')->ignore($pelanggan?->id)],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('pelanggans', 'email')->ignore($pelanggan?->id)],
            'telepon' => ['sometimes', 'required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'alamat' => ['sometimes', 'required', 'string', 'max:1000'],
            'nomor_sim' => ['sometimes', 'required', 'string', 'max:20', Rule::unique('pelanggans', 'nomor_sim')->ignore($pelanggan?->id)],
            'foto_ktp' => ['nullable', 'string', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'nik.digits' => 'NIK harus terdiri dari 16 angka.',
            'nik.unique' => 'NIK sudah terdaftar.',
            'email.unique' => 'Email sudah terdaftar.',
            'nomor_sim.unique' => 'Nomor SIM sudah terdaftar.',
            'telepon.regex' => 'Nomor telepon hanya boleh berisi angka, spasi, +, -, dan tanda kurung.',
        ];
    }
}
