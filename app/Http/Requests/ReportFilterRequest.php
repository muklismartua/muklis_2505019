<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('laporan.lihat') ?? false;
    }

    public function rules(): array
    {
        $dueEndRules = ['nullable', 'date'];
        if ($this->filled('jatuh_dari')) {
            $dueEndRules[] = 'after_or_equal:jatuh_dari';
        }

        return [
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'status' => ['nullable', Rule::in(['belum_lunas', 'lunas', 'batal'])],
            'jatuh_dari' => ['nullable', 'date'],
            'jatuh_sampai' => $dueEndRules,
        ];
    }
}
