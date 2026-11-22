<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pembelian.kelola') ?? false;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'tanggal' => ['required', 'date'],
            'diskon' => ['nullable', 'integer', 'min:0'],
            'metode_pembayaran' => ['required', Rule::in(['tunai', 'transfer', 'qris', 'lainnya'])],
            'catatan' => ['nullable', 'string', 'max:2000'],
            'perbarui_harga_beli' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:1000000'],
            'items.*.harga_beli' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Tambahkan minimal satu item pembelian.',
            'items.min' => 'Tambahkan minimal satu item pembelian.',
            'items.max' => 'Maksimal 50 item dalam satu pembelian.',
            'items.*.product_id.distinct' => 'Produk yang sama tidak boleh ditambahkan lebih dari satu kali.',
            'items.*.product_id.exists' => 'Produk yang dipilih tidak tersedia.',
            'items.*.qty.min' => 'Jumlah item minimal 1.',
        ];
    }
}
