<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('penjualan.kelola') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
