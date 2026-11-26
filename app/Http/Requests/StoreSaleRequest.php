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

    protected function prepareForValidation(): void
    {
        $numericFields = ['diskon', 'pajak', 'bayar'];
        $cleaned = [];

        foreach ($numericFields as $field) {
            if ($this->has($field)) {
                $cleaned[$field] = $this->bersihkanNominal($this->input($field));
            }
        }

        $items = collect($this->input('items', []))->map(function ($item) {
            if (array_key_exists('diskon', $item)) {
                $item['diskon'] = $this->bersihkanNominal($item['diskon']);
            }

            return $item;
        })->all();

        $this->merge(array_merge($cleaned, [
            'metode_pembayaran' => $this->input('metode_pembayaran') === 'hutang'
                ? 'tempo'
                : $this->input('metode_pembayaran'),
            'items' => $items,
        ]));
    }

    private function bersihkanNominal(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return preg_replace('/[^0-9-]/', '', $value);
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
            'metode_pembayaran' => ['required', Rule::in(['tunai', 'transfer', 'tempo', 'hutang'])],
            'catatan' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:1000000'],
            'items.*.diskon' => ['nullable', 'integer', 'min:0', function ($attribute, $value, $fail): void {
                $index = (int) str($attribute)->between('items.', '.diskon')->toString();
                $item = $this->input("items.{$index}", []);
                $product = \App\Models\Product::query()->find($item['product_id'] ?? null);
                $subtotal = $product ? $product->harga_jual * (int) ($item['qty'] ?? 0) : 0;

                if ((int) $value > $subtotal) {
                    $fail('Diskon item tidak boleh melebihi subtotal baris.');
                }
            }],
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
