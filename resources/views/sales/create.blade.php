@extends('layouts.app')

@section('title', 'Buat Penjualan')

@php
    $saleProducts = $products->map(fn ($product) => [
        'id' => $product->id,
        'sku' => $product->sku,
        'nama' => $product->nama,
        'harga_jual' => $product->harga_jual,
        'stok' => $product->stok,
    ])->values();
    $initialValues = [
        'diskon' => old('diskon', 0),
        'pajak' => old('pajak', 0),
        'pajak_persen' => old('pajak_persen', $defaultTaxRate),
        'bayar' => old('bayar', 0),
        'metode_pembayaran' => old('metode_pembayaran', 'tunai'),
    ];
@endphp

@section('content')
    <div class="mb-6">
        <a href="{{ route('sales.index') }}" class="text-sm font-medium text-indigo-700 hover:underline">&larr; Kembali ke penjualan</a>
        <h1 class="mt-3 text-2xl font-bold">Buat Penjualan</h1>
        <p class="mt-1 text-sm text-slate-600">Stok akan dikurangi setelah penjualan berhasil disimpan.</p>
    </div>

    @if ($errors->any())
        <div class="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
            <p class="font-semibold">Periksa kembali data penjualan:</p>
            <ul class="mt-2 list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($products->isEmpty())
        <div class="mb-5 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            Belum ada produk aktif. Tambahkan produk sebelum membuat penjualan.
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('sales.store') }}"
        x-data="saleForm(@js($saleProducts), @js(old('items', [])), @js($initialValues))"
        class="space-y-6"
    >
        @csrf

        <section class="grid gap-4 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-2">
            <div>
                <x-ui.select
                    name="customer_id"
                    label="Pelanggan (wajib untuk tempo)"
                    :options="$customers"
                    :selected="old('customer_id')"
                    placeholder="Pelanggan umum"
                    x-bind:required="metodePembayaran === 'tempo'"
                />
            </div>
            <x-ui.input name="tanggal" label="Tanggal penjualan" type="date" :value="old('tanggal', now()->toDateString())" required />
            <x-ui.select
                name="metode_pembayaran"
                label="Metode pembayaran"
                :options="['tunai' => 'Tunai', 'transfer' => 'Transfer', 'tempo' => 'Tempo']"
                :selected="old('metode_pembayaran', 'tunai')"
                :placeholder="null"
                x-model="metodePembayaran"
                required
            />
            <x-ui.input name="bayar" label="Bayar (rupiah)" type="number" min="0" step="1" :value="old('bayar', 0)" x-model.number="bayar" required />
            <x-ui.input name="diskon" label="Diskon transaksi (rupiah)" type="number" min="0" step="1" :value="old('diskon', 0)" x-model.number="diskon" />
            <x-ui.input
                name="pajak_persen"
                label="Pajak (%)"
                type="number"
                min="0"
                max="100"
                step="0.01"
                :value="old('pajak_persen', $defaultTaxRate)"
                x-model.number="pajakPersen"
                required
            />
            <input type="hidden" name="pajak" :value="pajak">
            <div class="sm:col-span-2">
                <label for="catatan" class="mb-1 block text-sm font-medium text-slate-700">Catatan <span class="font-normal text-slate-500">(opsional)</span></label>
                <textarea id="catatan" name="catatan" rows="2" maxlength="2000" class="w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('catatan') }}</textarea>
                @error('catatan')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <p class="text-xs text-slate-500 sm:col-span-2">
                Pembayaran tunai dan transfer harus melunasi total. Pembayaran tempo dapat dibayar sebagian.
            </p>
        </section>

        <section class="overflow-visible rounded-lg border border-slate-200 bg-white">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-semibold">Cari produk</h2>
                <div class="relative mt-3">
                    <label class="sr-only" for="cari-produk">Cari produk berdasarkan nama atau SKU</label>
                    <input
                        id="cari-produk"
                        type="search"
                        x-model="query"
                        autocomplete="off"
                        placeholder="Ketik nama produk atau SKU"
                        class="w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                    <div
                        x-cloak
                        x-show="hasilPencarian.length > 0"
                        class="absolute z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-md border border-slate-200 bg-white shadow-lg"
                    >
                        <template x-for="product in hasilPencarian" :key="product.id">
                            <button
                                type="button"
                                @click="tambahProduk(product)"
                                :disabled="product.stok < 1"
                                class="flex w-full items-center justify-between gap-4 border-b border-slate-100 px-4 py-3 text-left text-sm last:border-b-0 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <span>
                                    <span class="block font-medium" x-text="product.nama"></span>
                                    <span class="text-xs text-slate-500" x-text="`${product.sku} · stok ${product.stok}`"></span>
                                </span>
                                <span class="whitespace-nowrap text-slate-700" x-text="formatRupiah(product.harga_jual)"></span>
                            </button>
                        </template>
                    </div>
                </div>
                <p x-cloak x-show="query.trim() && hasilPencarian.length === 0" class="mt-2 text-sm text-slate-500">Produk tidak ditemukan.</p>
                <p x-cloak x-show="error" x-text="error" class="mt-2 text-sm text-red-700" role="alert"></p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="min-w-56 px-5 py-3">Produk</th>
                            <th class="w-32 px-5 py-3">Qty</th>
                            <th class="w-40 px-5 py-3 text-right">Harga jual</th>
                            <th class="w-40 px-5 py-3">Diskon item</th>
                            <th class="w-44 px-5 py-3 text-right">Subtotal</th>
                            <th class="w-16 px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(item, index) in items" :key="item.key">
                            <tr>
                                <td class="px-5 py-3">
                                    <input type="hidden" :name="`items[${index}][product_id]`" :value="item.product_id">
                                    <p class="font-medium" x-text="item.nama"></p>
                                    <p class="mt-1 text-xs text-slate-500" x-text="`${item.sku || '—'} · stok tersedia ${item.stok}`"></p>
                                </td>
                                <td class="px-5 py-3">
                                    <input
                                        x-model.number="item.qty"
                                        :name="`items[${index}][qty]`"
                                        type="number"
                                        min="1"
                                        :max="item.stok"
                                        step="1"
                                        required
                                        @input="ubahQty(item)"
                                        class="w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 text-right" x-text="formatRupiah(item.harga_jual)"></td>
                                <td class="px-5 py-3">
                                    <input
                                        x-model.number="item.diskon"
                                        :name="`items[${index}][diskon]`"
                                        type="number"
                                        min="0"
                                        :max="item.harga_jual * item.qty"
                                        step="1"
                                        class="w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 text-right" x-text="formatRupiah(subtotalItem(item))"></td>
                                <td class="px-5 py-3 text-right">
                                    <button type="button" @click="hapusItem(index)" aria-label="Hapus item" class="rounded px-2 py-1 text-red-700 hover:bg-red-50">&times;</button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="items.length === 0">
                            <td colspan="6" class="px-5 py-10 text-center text-slate-500">Belum ada produk di keranjang.</td>
                        </tr>
                    </tbody>
                    <tfoot class="border-t border-slate-200 bg-slate-50">
                        <tr>
                            <th colspan="4" class="px-5 py-3 text-right">Subtotal item</th>
                            <th class="whitespace-nowrap px-5 py-3 text-right" x-text="formatRupiah(subtotal)"></th>
                            <th></th>
                        </tr>
                        <tr>
                            <th colspan="4" class="px-5 py-3 text-right">Pajak (<span x-text="pajakPersen"></span>%)</th>
                            <th class="whitespace-nowrap px-5 py-3 text-right" x-text="formatRupiah(pajak)"></th>
                            <th></th>
                        </tr>
                        <tr>
                            <th colspan="4" class="px-5 py-3 text-right">Total setelah diskon dan pajak</th>
                            <th class="whitespace-nowrap px-5 py-3 text-right text-base font-bold" x-text="formatRupiah(total)"></th>
                            <th></th>
                        </tr>
                        <tr>
                            <th colspan="4" class="px-5 py-3 text-right">Kembalian</th>
                            <th class="whitespace-nowrap px-5 py-3 text-right font-semibold" x-text="formatRupiah(kembalian)"></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>

        <div class="flex justify-end gap-3">
            <a href="{{ route('sales.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold hover:bg-white">Batal</a>
            <x-ui.button type="submit" :disabled="$products->isEmpty()">Simpan penjualan</x-ui.button>
        </div>
    </form>
@endsection
