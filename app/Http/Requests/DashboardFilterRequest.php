<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DashboardFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('dashboard.lihat') ?? false;
    }

    public function rules(): array
    {
        return [
            'hari' => ['nullable', 'integer', Rule::in([7, 30])],
        ];
    }
}
