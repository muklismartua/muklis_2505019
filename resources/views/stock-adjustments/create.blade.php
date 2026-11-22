@extends('layouts.app')

@section('title', 'Buat Stok Opname')

@section('content')
    <div class="mb-6">
        <a href="{{ route('stock-adjustments.index') }}" class="text-sm font-medium text-indigo-700 hover:underline">&larr; Kembali ke stok opname</a>
        <h1 class="mt-3 text-2xl font-bold">Buat Stok Opname</h1>
        <p class="mt-1 text-sm text-slate-600">Simpan jumlah fisik untuk menyesuaikan stok sistem.</p>
    </div>

    <form method="POST" action="{{ route('stock-adjustments.store') }}" class="max-w-2xl space-y-5 rounded-lg border border-slate-200 bg-white p-6">
        @csrf
        <x-ui.select
            name="product_id"
            label="Produk"
            :options="$products->mapWithKeys(fn ($product) => [$product->id => $product->nama.' ('.$product->sku.') — stok '.$product->stok])"
            :selected="old('product_id', request('product_id'))"
            placeholder="Pilih produk"
            required
        />
        <x-ui.input name="qty_fisik" label="Jumlah fisik" type="number" :value="old('qty_fisik')" required min="0" step="1" />
        <div>
            <label for="alasan" class="mb-1 block text-sm font-medium text-slate-700">Alasan penyesuaian <span class="text-red-600" aria-hidden="true">*</span></label>
            <textarea id="alasan" name="alasan" rows="3" maxlength="2000" required class="w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('alasan') }}</textarea>
            @error('alasan')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
        @if ($products->isEmpty())
            <p class="rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-800">Belum ada produk yang dapat disesuaikan.</p>
        @endif
        <div class="flex justify-end gap-3">
            <a href="{{ route('stock-adjustments.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold hover:bg-slate-50">Batal</a>
            <x-ui.button type="submit" :disabled="$products->isEmpty()">Simpan opname</x-ui.button>
        </div>
    </form>
@endsection
