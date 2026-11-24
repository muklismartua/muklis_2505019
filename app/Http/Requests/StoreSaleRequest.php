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
            'customer_id' => [
                'nullable',
                'required_if:metode_pembayaran,tempo',
                'integer',
                Rule::exists('customers', 'id')->where('aktif', true),
            ],
            'tanggal' => ['required', 'date'],
            'jatuh_tempo' => ['nullable', 'required_if:metode_pembayaran,tempo', 'date', 'after:today'],
            'diskon' => ['nullable', 'integer', 'min:0'],
            'pajak' => ['nullable', 'integer', 'min:0'],
            'pajak_persen' => ['nullable', 'numeric', 'between:0,100'],
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
            'pajak_persen.between' => 'Persentase pajak harus antara 0 sampai 100.',
            'customer_id.required_if' => 'Pelanggan wajib dipilih untuk penjualan dengan pembayaran tempo.',
            'jatuh_tempo.required_if' => 'Tanggal jatuh tempo wajib diisi untuk pembayaran hutang.',
            'jatuh_tempo.after' => 'Tanggal jatuh tempo harus setelah hari ini.',
        ];
    }
}
