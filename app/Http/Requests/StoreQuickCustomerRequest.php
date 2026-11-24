<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuickCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAny(['pelanggan.buat', 'penjualan.kelola']) ?? false;
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'telepon' => ['nullable', 'string', 'max:50'],
            'alamat' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama pelanggan wajib diisi.',
            'nama.max' => 'Nama pelanggan maksimal 255 karakter.',
            'telepon.max' => 'Nomor telepon maksimal 50 karakter.',
            'alamat.max' => 'Alamat maksimal 2.000 karakter.',
        ];
    }
}
