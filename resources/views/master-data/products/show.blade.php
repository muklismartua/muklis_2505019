@extends('layouts.app')

@section('title', 'Detail Produk')

@section('content')
    <div class="mb-6">
        <a href="{{ route('products.index') }}" class="text-sm font-medium text-indigo-700 hover:underline">&larr; Kembali ke produk</a>
        <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">{{ $product->nama }}</h1>
                <p class="mt-1 text-sm text-slate-600">{{ $product->sku }}</p>
            </div>
            @can('produk.kelola')
                <a href="{{ route('products.edit', $product) }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Edit produk</a>
            @endcan
        </div>
    </div>

    <section class="grid gap-6 rounded-lg border border-slate-200 bg-white p-6 sm:grid-cols-[12rem_1fr]">
        @if ($product->foto)
            <img src="{{ Storage::disk('public')->url($product->foto) }}" alt="Foto {{ $product->nama }}" class="h-48 w-48 rounded-lg object-cover">
        @else
            <div class="flex h-48 w-48 items-center justify-center rounded-lg bg-slate-100 text-sm text-slate-400">Tidak ada foto</div>
        @endif
        <dl class="grid gap-4 sm:grid-cols-2">
            <div><dt class="text-xs font-medium uppercase text-slate-500">Kategori</dt><dd class="mt-1">{{ $product->category?->nama ?? '—' }}</dd></div>
            <div><dt class="text-xs font-medium uppercase text-slate-500">Satuan</dt><dd class="mt-1">{{ $product->unit?->nama ?? '—' }} ({{ $product->unit?->singkatan ?? '—' }})</dd></div>
            <div><dt class="text-xs font-medium uppercase text-slate-500">Harga beli</dt><dd class="mt-1">Rp {{ number_format($product->harga_beli, 0, ',', '.') }}</dd></div>
            <div><dt class="text-xs font-medium uppercase text-slate-500">Harga jual</dt><dd class="mt-1">Rp {{ number_format($product->harga_jual, 0, ',', '.') }}</dd></div>
            <div><dt class="text-xs font-medium uppercase text-slate-500">Stok</dt><dd class="mt-1">{{ number_format($product->stok, 0, ',', '.') }}</dd></div>
            <div><dt class="text-xs font-medium uppercase text-slate-500">Stok minimum</dt><dd class="mt-1">{{ number_format($product->stok_minimum, 0, ',', '.') }}</dd></div>
            <div><dt class="text-xs font-medium uppercase text-slate-500">Status</dt><dd class="mt-1">{{ $product->aktif ? 'Aktif' : 'Nonaktif' }}</dd></div>
        </dl>
    </section>
@endsection
