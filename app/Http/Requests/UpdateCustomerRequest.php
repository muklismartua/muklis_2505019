<?php

namespace App\Http\Requests;

class UpdateCustomerRequest extends LookupRequest
{
    protected function permission(): string
    {
        return 'pelanggan.kelola';
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'telepon' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'alamat' => ['nullable', 'string', 'max:2000'],
            'batas_hutang' => ['sometimes', 'required', 'integer', 'min:0'],
            'aktif' => ['sometimes', 'boolean'],
        ];
    }
}
