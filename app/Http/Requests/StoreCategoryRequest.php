<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StoreCategoryRequest extends LookupRequest
{
    protected function permission(): string
    {
        return 'kategori.kelola';
    }

    public function rules(): array
    {
        return ['nama' => ['required', 'string', 'max:255', Rule::unique('categories', 'nama')]];
    }
}
