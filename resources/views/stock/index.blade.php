@extends('layouts.app')

@section('title', 'Kartu Stok')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold">Kartu Stok</h1>
            <p class="mt-1 text-sm text-slate-600">Pilih produk untuk melihat riwayat pergerakan stok.</p>
        </div>
        <a href="{{ route('stock.low-stock') }}" class="rounded-md border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-800 hover:bg-amber-100">Daftar stok menipis</a>
    </div>

    <form method="GET" action="{{ route('stock.index') }}" class="mb-5 flex items-end gap-3 rounded-lg border border-slate-200 bg-white p-4">
        <div class="min-w-0 flex-1">
            <x-ui.input name="q" label="Cari produk" label-class="sr-only" :value="request('q')" placeholder="Cari SKU atau nama produk..." />
        </div>
        <x-ui.button type="submit" variant="secondary">Cari</x-ui.button>
    </form>

    <x-ui.table :headers="['Produk', 'Stok saat ini', 'Stok minimum', 'Aksi']" :paginator="$products">
        @forelse ($products as $product)
            <tr>
                <td class="px-5 py-4">
                    <p class="font-medium">{{ $product->nama }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $product->sku }}</p>
                </td>
                <td class="whitespace-nowrap px-5 py-4 text-right">{{ number_format($product->stok, 0, ',', '.') }} {{ $product->unit?->singkatan }}</td>
                <td class="whitespace-nowrap px-5 py-4 text-right">{{ number_format($product->stok_minimum, 0, ',', '.') }}</td>
                <td class="whitespace-nowrap px-5 py-4 text-right">
                    <a href="{{ route('stock.show', $product) }}" class="font-medium text-indigo-700 hover:underline">Lihat kartu stok</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="px-5 py-10 text-center text-slate-500">Belum ada produk.</td></tr>
        @endforelse
    </x-ui.table>
@endsection
