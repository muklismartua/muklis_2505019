<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAny(['pembelian.lihat', 'pembelian.kelola']) ?? false;
    }

    public function rules(): array
    {
        return [
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'status' => ['nullable', Rule::in(['selesai', 'batal'])],
        ];
    }
}
