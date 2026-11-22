<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->hasRole('admin')
            && $this->user()?->can('pengaturan.kelola'));
    }

    public function rules(): array
    {
        return [
            'nama_toko' => ['required', 'string', 'max:255'],
            'alamat' => ['nullable', 'string', 'max:2000'],
            'telepon' => ['nullable', 'string', 'max:50'],
            'prefix_pembelian' => ['required', 'string', 'max:10', 'regex:/^[A-Z0-9]+$/'],
            'prefix_penjualan' => ['required', 'string', 'max:10', 'regex:/^[A-Z0-9]+$/'],
            'persentase_pajak_default' => ['required', 'numeric', 'between:0,100'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'hapus_logo' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_toko.required' => 'Nama toko wajib diisi.',
            'prefix_pembelian.regex' => 'Prefix pembelian hanya boleh berisi huruf kapital dan angka.',
            'prefix_penjualan.regex' => 'Prefix penjualan hanya boleh berisi huruf kapital dan angka.',
            'persentase_pajak_default.between' => 'Persentase pajak harus antara 0 sampai 100.',
            'logo.image' => 'Logo harus berupa berkas gambar.',
            'logo.mimes' => 'Format logo yang diperbolehkan: JPG, PNG, atau WebP.',
            'logo.max' => 'Ukuran logo maksimal 2 MB.',
        ];
    }
}
