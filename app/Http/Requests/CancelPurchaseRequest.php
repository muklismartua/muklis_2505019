<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pembelian.kelola') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
