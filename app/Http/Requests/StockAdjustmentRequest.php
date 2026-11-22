<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('stok.kelola') ?? false;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'qty_fisik' => ['required', 'integer', 'min:0'],
            'alasan' => ['required', 'string', 'max:2000'],
        ];
    }
}
