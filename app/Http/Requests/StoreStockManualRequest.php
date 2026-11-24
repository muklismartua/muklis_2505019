<?php

namespace App\Http\Requests;

use App\Models\StockManual;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockManualRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('stok.kelola') ?? false;
    }

    public function rules(): array
    {
        return [
            'tipe' => ['required', Rule::in([StockManual::TYPE_ADD, StockManual::TYPE_SUBTRACT])],
            'alasan' => ['required', Rule::in(array_keys(StockManual::REASONS))],
            'keterangan' => ['nullable', 'string', 'max:2000'],
            'tanggal' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:1000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipe.in' => 'Pilih tipe perubahan stok yang valid.',
            'alasan.in' => 'Pilih alasan perubahan stok yang valid.',
            'items.required' => 'Tambahkan minimal satu produk.',
            'items.min' => 'Tambahkan minimal satu produk.',
            'items.max' => 'Maksimal 50 produk dalam satu perubahan stok.',
            'items.*.product_id.distinct' => 'Produk yang sama tidak boleh ditambahkan lebih dari satu kali.',
            'items.*.product_id.exists' => 'Produk yang dipilih tidak tersedia.',
            'items.*.qty.min' => 'Jumlah perubahan stok minimal 1.',
        ];
    }
}
