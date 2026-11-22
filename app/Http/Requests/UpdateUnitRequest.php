<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateUnitRequest extends LookupRequest
{
    protected function permission(): string
    {
        return 'satuan.kelola';
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255', Rule::unique('units', 'nama')->ignore($this->route('unit'))],
            'singkatan' => ['required', 'string', 'max:20'],
        ];
    }
}
