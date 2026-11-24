<?php

namespace App\Http\Requests;

use App\Models\StockManual;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockManualFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAny(['stok.lihat', 'stok.kelola']) ?? false;
    }

    public function rules(): array
    {
        return [
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
            'tipe' => ['nullable', Rule::in([StockManual::TYPE_ADD, StockManual::TYPE_SUBTRACT])],
            'alasan' => ['nullable', Rule::in(array_keys(StockManual::REASONS))],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'sampai.after_or_equal' => 'Tanggal akhir harus sama dengan atau setelah tanggal mulai.',
            'product_id.exists' => 'Produk yang dipilih tidak ditemukan.',
        ];
    }
}
