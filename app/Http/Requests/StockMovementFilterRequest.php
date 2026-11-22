<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockMovementFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('stok.lihat') ?? false;
    }

    public function rules(): array
    {
        return [
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
        ];
    }
}
