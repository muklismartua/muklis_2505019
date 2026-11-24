<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHutangSupplierPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('hutang.kelola') ?? false;
    }

    public function rules(): array
    {
        return [
            'jumlah' => ['required', 'integer', 'min:1'],
            'metode' => ['required', Rule::in(['tunai', 'transfer'])],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
