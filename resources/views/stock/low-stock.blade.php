@extends('layouts.app')

@section('title', 'Stok Menipis')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <a href="{{ route('stock.index') }}" class="text-sm font-medium text-indigo-700 hover:underline">&larr; Kembali ke kartu stok</a>
            <h1 class="mt-3 text-2xl font-bold">Stok Menipis</h1>
            <p class="mt-1 text-sm text-slate-600">Produk dengan stok sama dengan atau di bawah stok minimum.</p>
        </div>
    </div>

    <x-ui.table :headers="['Produk', 'Kategori', 'Stok', 'Stok minimum', 'Status', 'Aksi']" :paginator="$products">
        @forelse ($products as $product)
            <tr>
                <td class="px-5 py-4">
                    <a href="{{ route('stock.show', $product) }}" class="font-medium text-indigo-700 hover:underline">{{ $product->nama }}</a>
                    <p class="mt-1 text-xs text-slate-500">{{ $product->sku }}</p>
                </td>
                <td class="whitespace-nowrap px-5 py-4">{{ $product->category?->nama ?? '—' }}</td>
                <td class="whitespace-nowrap px-5 py-4 text-right">{{ number_format($product->stok, 0, ',', '.') }} {{ $product->unit?->singkatan }}</td>
                <td class="whitespace-nowrap px-5 py-4 text-right">{{ number_format($product->stok_minimum, 0, ',', '.') }}</td>
                <td class="whitespace-nowrap px-5 py-4">
                    <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">Stok menipis</span>
                </td>
                <td class="whitespace-nowrap px-5 py-4">
                    @can('stok.kelola')
                        <a href="{{ route('stock-manuals.create', ['product_id' => $product->id]) }}" class="font-medium text-indigo-700 hover:underline">Tambah/Kurang Stok</a>
                    @else
                        —
                    @endcan
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">Tidak ada produk dengan stok menipis.</td></tr>
        @endforelse
    </x-ui.table>
@endsection
