<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePenyewaanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mobil_id' => ['required', 'integer', 'exists:mobils,id'],
            'pelanggan_id' => ['required', 'integer', 'exists:pelanggans,id'],
            'tanggal_mulai' => ['required', 'date', 'after_or_equal:today'],
            'tanggal_selesai' => ['required', 'date', 'after:tanggal_mulai'],
            'status' => ['sometimes', Rule::in(['booked', 'berjalan'])],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'mobil_id.required' => 'Kolom mobil wajib dipilih.',
            'mobil_id.exists' => 'Mobil tidak ditemukan.',
            'pelanggan_id.required' => 'Kolom pelanggan wajib dipilih.',
            'pelanggan_id.exists' => 'Pelanggan tidak ditemukan.',
            'tanggal_mulai.required' => 'Tanggal mulai wajib diisi.',
            'tanggal_mulai.after_or_equal' => 'Tanggal mulai tidak boleh di masa lalu.',
            'tanggal_selesai.required' => 'Tanggal selesai wajib diisi.',
            'tanggal_selesai.after' => 'Tanggal selesai harus setelah tanggal mulai.',
            'status.in' => 'Status penyewaan baru harus booked atau berjalan.',
        ];
    }
}
