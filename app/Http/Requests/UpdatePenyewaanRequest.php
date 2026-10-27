<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePenyewaanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mobil_id' => ['sometimes', 'required', 'integer', 'exists:mobils,id'],
            'pelanggan_id' => ['sometimes', 'required', 'integer', 'exists:pelanggans,id'],
            'tanggal_mulai' => ['sometimes', 'required', 'date'],
            'tanggal_selesai' => ['sometimes', 'required', 'date'],
            'status' => ['sometimes', 'required', Rule::in(['booked', 'berjalan', 'selesai', 'batal'])],
            'tanggal_dikembalikan' => ['nullable', 'date'],
            'denda' => ['sometimes', 'integer', 'min:0'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Status harus salah satu dari: booked, berjalan, selesai, batal.',
            'tanggal_selesai.after' => 'Tanggal selesai harus setelah tanggal mulai.',
        ];
    }
}
