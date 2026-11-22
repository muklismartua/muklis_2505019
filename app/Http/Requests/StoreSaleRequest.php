<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('penjualan.kelola') ?? false;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'tanggal' => ['required', 'date'],
            'diskon' => ['nullable', 'integer', 'min:0'],
            'pajak' => ['required', 'integer', 'min:0'],
            'bayar' => ['required', 'integer', 'min:0'],
            'metode_pembayaran' => ['required', Rule::in(['tunai', 'transfer', 'tempo'])],
            'catatan' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:1000000'],
            'items.*.diskon' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Tambahkan minimal satu produk ke keranjang.',
            'items.min' => 'Tambahkan minimal satu produk ke keranjang.',
            'items.max' => 'Maksimal 50 produk dalam satu penjualan.',
            'items.*.product_id.distinct' => 'Produk yang sama tidak boleh ditambahkan lebih dari satu kali.',
            'items.*.product_id.exists' => 'Produk yang dipilih tidak tersedia.',
            'items.*.qty.min' => 'Jumlah produk minimal 1.',
        ];
    }
}
