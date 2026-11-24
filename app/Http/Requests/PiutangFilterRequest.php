<?php

namespace App\Http\Requests;

use App\Models\Piutang;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PiutangFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAny(['piutang.lihat', 'piutang.kelola']) ?? false;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'status' => ['nullable', Rule::in([
                Piutang::STATUS_UNPAID,
                Piutang::STATUS_PAID,
                Piutang::STATUS_CANCELLED,
            ])],
            'jatuh_tempo' => ['nullable', 'date'],
        ];
    }
}
