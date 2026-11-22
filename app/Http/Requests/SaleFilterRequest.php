<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaleFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAny(['penjualan.lihat', 'penjualan.kelola']) ?? false;
    }

    public function rules(): array
    {
        return [
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'status' => ['nullable', Rule::in(['selesai', 'batal'])],
            'metode_pembayaran' => ['nullable', Rule::in(['tunai', 'transfer', 'tempo'])],
        ];
    }
}
