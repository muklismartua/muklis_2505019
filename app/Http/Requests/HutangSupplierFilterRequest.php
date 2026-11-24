<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HutangSupplierFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAny(['hutang.lihat', 'hutang.kelola']) ?? false;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'status' => ['nullable', Rule::in(['belum_lunas', 'lunas', 'batal'])],
            'jatuh_dari' => ['nullable', 'date'],
            'jatuh_sampai' => ['nullable', 'date', 'after_or_equal:jatuh_dari'],
        ];
    }
}
