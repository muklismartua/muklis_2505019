<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StoreUnitRequest extends LookupRequest
{
    protected function permission(): string
    {
        return 'satuan.kelola';
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255', Rule::unique('units', 'nama')],
            'singkatan' => ['required', 'string', 'max:20'],
        ];
    }
}
