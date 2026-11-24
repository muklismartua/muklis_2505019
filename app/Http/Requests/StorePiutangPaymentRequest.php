<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePiutangPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('piutang.kelola') ?? false;
    }

    public function rules(): array
    {
        return [
            'jumlah' => ['required', 'integer', 'min:1'],
            'metode' => ['required', Rule::in(['tunai', 'transfer'])],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'jumlah.required' => 'Jumlah pembayaran wajib diisi.',
            'jumlah.integer' => 'Jumlah pembayaran harus berupa bilangan bulat rupiah.',
            'jumlah.min' => 'Jumlah pembayaran harus lebih besar dari nol.',
            'metode.required' => 'Metode pembayaran wajib dipilih.',
            'metode.in' => 'Metode pembayaran harus tunai atau transfer.',
            'catatan.max' => 'Catatan maksimal 2.000 karakter.',
        ];
    }
}
