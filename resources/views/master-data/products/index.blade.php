@extends('layouts.app')

@section('title', 'Produk')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold">Produk</h1>
            <p class="mt-1 text-sm text-slate-600">Kelola katalog, harga, dan status produk.</p>
        </div>
        @can('produk.kelola')
            <a href="{{ route('products.create') }}" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Tambah produk</a>
        @endcan
    </div>

    <form method="GET" action="{{ route('products.index') }}" class="mb-5 grid gap-3 rounded-lg border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.input name="q" label="Cari produk" label-class="sr-only" :value="request('q')" placeholder="Cari SKU atau nama produk..." />
        <x-ui.select name="category_id" label="Kategori" label-class="sr-only" :options="$categories" :selected="request('category_id')" placeholder="Semua kategori" />
        <x-ui.select name="status" label="Status" label-class="sr-only" :options="['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif']" :selected="request('status')" placeholder="Semua status" />
        <x-ui.button type="submit" variant="secondary">Cari / Filter</x-ui.button>
    </form>

    <x-ui.table :headers="['Produk', 'Kategori', 'Satuan', 'Harga beli', 'Harga jual', 'Stok', 'Status', 'Aksi']" :paginator="$products">
        @forelse ($products as $product)
            <tr>
                <td class="min-w-56 px-5 py-4">
                    <a href="{{ route('products.show', $product) }}" class="font-medium text-indigo-700 hover:underline">{{ $product->nama }}</a>
                    <p class="mt-1 text-xs text-slate-500">{{ $product->sku }}</p>
                </td>
                <td class="whitespace-nowrap px-5 py-4">{{ $product->category?->nama ?? '—' }}</td>
                <td class="whitespace-nowrap px-5 py-4">{{ $product->unit?->singkatan ?? '—' }}</td>
                <td class="whitespace-nowrap px-5 py-4 text-right">Rp {{ number_format($product->harga_beli, 0, ',', '.') }}</td>
                <td class="whitespace-nowrap px-5 py-4 text-right">Rp {{ number_format($product->harga_jual, 0, ',', '.') }}</td>
                <td class="whitespace-nowrap px-5 py-4 text-right">{{ number_format($product->stok, 0, ',', '.') }}</td>
                <td class="whitespace-nowrap px-5 py-4">
                    <x-ui.badge :status="$product->aktif ? 'active' : 'inactive'">{{ $product->aktif ? 'Aktif' : 'Nonaktif' }}</x-ui.badge>
                </td>
                <td class="whitespace-nowrap px-5 py-4 text-right">
                    @can('produk.kelola')
                        <div class="inline-flex items-center gap-3">
                            <a href="{{ route('products.edit', $product) }}" class="font-medium text-indigo-700 hover:underline">Edit</a>
                            @php($formId = 'delete-product-'.$product->id)
                            <form id="{{ $formId }}" method="POST" action="{{ route('products.destroy', $product) }}">
                                @csrf
                                @method('DELETE')
                            </form>
                            <x-ui.confirm-modal :form-id="$formId" :id="$formId.'-title'" title="Hapus produk?" confirm-text="Hapus">
                                <x-slot:trigger><button type="button" class="font-medium text-red-700 hover:underline">Hapus</button></x-slot:trigger>
                                Produk ini akan diarsipkan dan tidak muncul lagi pada transaksi baru.
                            </x-ui.confirm-modal>
                        </div>
                    @endcan
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="px-5 py-10 text-center text-slate-500">Belum ada produk.</td></tr>
        @endforelse
    </x-ui.table>
@endsection
