@extends('layouts.app')

@section('title', 'Tambah/Kurang Stok Manual')

@php
    $productOptions = $products->map(fn ($product) => [
        'id' => $product->id,
        'sku' => $product->sku,
        'nama' => $product->nama,
        'stok' => $product->stok,
    ])->values();
@endphp

@section('content')
    <div class="mb-6">
        <a href="{{ route('stock-manuals.index') }}" class="text-sm font-medium text-indigo-700 hover:underline">&larr; Kembali ke riwayat stok manual</a>
        <h1 class="mt-3 text-2xl font-bold">Tambah/Kurang Stok Manual</h1>
        <p class="mt-1 text-sm text-slate-600">Perubahan semua item disimpan sekaligus dengan satu alasan.</p>
    </div>

    @if ($products->isEmpty())
        <div class="mb-5 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            Belum ada produk yang dapat diubah stoknya.
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('stock-manuals.store') }}"
        x-data="stockManualForm(@js($productOptions), @js(old('items', [])), @js($selectedProductId), @js(old('tipe', 'tambah')))"
        class="space-y-6"
    >
        @csrf

        <section class="grid gap-5 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <span class="mb-2 block text-sm font-medium text-slate-700">Tipe perubahan</span>
                <div class="inline-flex rounded-lg border border-slate-300 p-1" role="group" aria-label="Tipe perubahan stok">
                    <label class="cursor-pointer">
                        <input class="sr-only" type="radio" name="tipe" value="tambah" x-model="tipe">
                        <span class="inline-flex rounded-md px-4 py-2 text-sm font-semibold" :class="tipe === 'tambah' ? 'bg-emerald-600 text-white' : 'text-slate-700 hover:bg-slate-100'">Tambah</span>
                    </label>
                    <label class="cursor-pointer">
                        <input class="sr-only" type="radio" name="tipe" value="kurang" x-model="tipe">
                        <span class="inline-flex rounded-md px-4 py-2 text-sm font-semibold" :class="tipe === 'kurang' ? 'bg-red-600 text-white' : 'text-slate-700 hover:bg-slate-100'">Kurang</span>
                    </label>
                </div>
                @error('tipe')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>

            <x-ui.input name="tanggal" label="Tanggal" type="date" :value="old('tanggal', now()->toDateString())" required />
            <x-ui.select name="alasan" label="Alasan" :options="$reasons" :selected="old('alasan')" placeholder="Pilih alasan" required />
            @error('alasan')<p class="text-sm text-red-700">{{ $message }}</p>@enderror

            <div class="sm:col-span-2">
                <label for="keterangan" class="mb-1 block text-sm font-medium text-slate-700">Keterangan <span class="font-normal text-slate-500">(opsional)</span></label>
                <textarea id="keterangan" name="keterangan" rows="2" maxlength="2000" class="w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('keterangan') }}</textarea>
                @error('keterangan')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
        </section>

        <section class="overflow-visible rounded-lg border border-slate-200 bg-white">
            <div class="border-b border-slate-200 p-5">
                <label for="cari-produk-manual" class="block text-sm font-semibold text-slate-800">Cari dan tambahkan produk</label>
                <div class="relative mt-2">
                    <input
                        id="cari-produk-manual"
                        type="search"
                        x-model="query"
                        autocomplete="off"
                        placeholder="Ketik nama produk atau SKU"
                        class="w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                    <div x-cloak x-show="hasilPencarian.length > 0" class="absolute z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-md border border-slate-200 bg-white shadow-lg">
                        <template x-for="product in hasilPencarian" :key="product.id">
                            <button
                                type="button"
                                @click="tambahProduk(product)"
                                class="flex w-full items-center justify-between gap-4 border-b border-slate-100 px-4 py-3 text-left text-sm last:border-b-0 hover:bg-slate-50"
                            >
                                <span>
                                    <span class="block font-medium" x-text="product.nama"></span>
                                    <span class="text-xs text-slate-500" x-text="`${product.sku} · stok ${formatJumlah(product.stok)}`"></span>
                                </span>
                                <span class="text-xs text-indigo-700">Tambahkan</span>
                            </button>
                        </template>
                    </div>
                </div>
                <p x-cloak x-show="query.trim() && hasilPencarian.length === 0" class="mt-2 text-sm text-slate-500">Produk tidak ditemukan atau sudah ada di daftar.</p>
                @error('items')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="min-w-56 px-5 py-3">Produk</th>
                            <th class="w-36 px-5 py-3">Jumlah</th>
                            <th class="w-40 px-5 py-3 text-right">Stok sekarang</th>
                            <th class="w-40 px-5 py-3 text-right">Stok setelah</th>
                            <th class="w-16 px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(item, index) in items" :key="item.key">
                            <tr>
                                <td class="px-5 py-3">
                                    <input type="hidden" :name="`items[${index}][product_id]`" :value="item.product_id">
                                    <p class="font-medium" x-text="item.nama"></p>
                                    <p class="mt-1 text-xs text-slate-500" x-text="item.sku"></p>
                                </td>
                                <td class="px-5 py-3">
                                    <input
                                        x-model.number="item.qty"
                                        :name="`items[${index}][qty]`"
                                        type="number"
                                        min="1"
                                        :max="tipe === 'kurang' ? item.stok : 1000000"
                                        step="1"
                                        required
                                        class="w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 text-right" x-text="formatJumlah(item.stok)"></td>
                                <td class="whitespace-nowrap px-5 py-3 text-right font-semibold" :class="stokSetelah(item) < 0 ? 'text-red-700' : 'text-slate-900'" x-text="formatJumlah(stokSetelah(item))"></td>
                                <td class="px-5 py-3 text-right">
                                    <button type="button" @click="hapusItem(index)" aria-label="Hapus produk" class="rounded px-2 py-1 text-red-700 hover:bg-red-50">&times;</button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="items.length === 0">
                            <td colspan="5" class="px-5 py-10 text-center text-slate-500">Belum ada produk di daftar perubahan stok.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 px-5 py-3">
                <p x-cloak x-show="adaQtyTidakValid" class="text-sm text-red-700" role="alert">Jumlah harus lebih dari nol dan stok setelah perubahan tidak boleh negatif.</p>
            </div>
        </section>

        <div class="flex justify-end gap-3">
            <a href="{{ route('stock-manuals.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold hover:bg-white">Batal</a>
            <x-ui.button type="submit" ::disabled="items.length === 0 || adaQtyTidakValid" :disabled="$products->isEmpty()">Simpan perubahan stok</x-ui.button>
        </div>
    </form>
@endsection
