@extends('layouts.app')

@section('title', ($product->exists ? 'Edit ' : 'Tambah ').'Produk')

@section('content')
    <div class="mb-6">
        <a href="{{ route('products.index') }}" class="text-sm font-medium text-indigo-700 hover:underline">&larr; Kembali ke produk</a>
        <h1 class="mt-3 text-2xl font-bold">{{ $product->exists ? 'Edit produk' : 'Tambah produk' }}</h1>
    </div>

    <form method="POST" enctype="multipart/form-data" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}" class="max-w-3xl space-y-5 rounded-lg border border-slate-200 bg-white p-6">
        @csrf
        @if ($product->exists)
            @method('PUT')
        @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <x-ui.input name="sku" label="SKU" :value="old('sku', $product->sku)" required maxlength="100" />
            <x-ui.input name="nama" label="Nama produk" :value="old('nama', $product->nama)" required maxlength="255" />
            <x-ui.select name="category_id" label="Kategori" :options="$categories" :selected="old('category_id', $product->category_id)" placeholder="Pilih kategori" required />
            <x-ui.select name="unit_id" label="Satuan" :options="$units" :selected="old('unit_id', $product->unit_id)" placeholder="Pilih satuan" required />
            <x-ui.input name="harga_beli" label="Harga beli (rupiah)" type="number" :value="old('harga_beli', $product->harga_beli ?? 0)" required min="0" step="1" />
            <x-ui.input name="harga_jual" label="Harga jual (rupiah)" type="number" :value="old('harga_jual', $product->harga_jual ?? 0)" required min="0" step="1" />
            <x-ui.input name="stok_minimum" label="Stok minimum" type="number" :value="old('stok_minimum', $product->stok_minimum ?? 0)" required min="0" step="1" />
            <div>
                <x-ui.input name="foto" label="Foto produk" type="file" accept="image/jpeg,image/png,image/webp" />
                <p class="mt-1 text-xs text-slate-500">JPG, PNG, atau WebP, maksimal 2 MB.</p>
                @if ($product->foto)
                    <img src="{{ Storage::disk('public')->url($product->foto) }}" alt="Foto {{ $product->nama }}" class="mt-3 h-24 w-24 rounded-md object-cover">
                @endif
            </div>
        </div>

        <div>
            <input type="hidden" name="aktif" value="0">
            <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                <input type="checkbox" name="aktif" value="1" @checked((bool) old('aktif', $product->exists ? $product->aktif : true)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                Produk aktif
            </label>
            @error('aktif')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('products.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold hover:bg-slate-50">Batal</a>
            <x-ui.button type="submit">{{ $product->exists ? 'Simpan perubahan' : 'Simpan produk' }}</x-ui.button>
        </div>
    </form>
@endsection
