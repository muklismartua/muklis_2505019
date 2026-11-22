<?php

namespace App\Http\Requests;

class StoreSupplierRequest extends LookupRequest
{
    protected function permission(): string
    {
        return 'pemasok.kelola';
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'telepon' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'alamat' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
