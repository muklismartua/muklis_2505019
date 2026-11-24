@extends('layouts.app')

@section('title', 'Buat Pembelian')

@php
    $purchaseProducts = $products->map(fn ($product) => [
        'id' => $product->id,
        'sku' => $product->sku,
        'nama' => $product->nama,
        'harga_beli' => $product->harga_beli,
        'stok' => $product->stok,
    ])->values();
    $initialItems = old('items', [['product_id' => '', 'qty' => 1, 'harga_beli' => 0]]);
@endphp

@section('content')
    <div class="mb-6">
        <a href="{{ route('purchases.index') }}" class="text-sm font-medium text-indigo-700 hover:underline">&larr; Kembali ke pembelian</a>
        <h1 class="mt-3 text-2xl font-bold">Buat Pembelian</h1>
        <p class="mt-1 text-sm text-slate-600">Stok akan bertambah setelah pembelian berhasil disimpan.</p>
    </div>

    @if ($suppliers->isEmpty() || $products->isEmpty())
        <div class="mb-5 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            Tambahkan supplier dan produk aktif sebelum membuat pembelian.
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('purchases.store') }}"
        x-data="purchaseForm(@js($purchaseProducts), @js($initialItems), @js(old('diskon', 0)), @js(old('perbarui_harga_beli', false)), @js(old('metode_pembayaran', 'tunai')), @js(old('bayar', 0)))"
        class="space-y-6"
    >
        @csrf
        <section class="grid gap-4 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-2">
            <x-ui.select name="supplier_id" label="Supplier" :options="$suppliers" :selected="old('supplier_id')" placeholder="Pilih supplier" required />
            <x-ui.input name="tanggal" label="Tanggal pembelian" type="date" :value="old('tanggal', now()->toDateString())" required />
            <x-ui.select
                name="metode_pembayaran"
                label="Metode pembayaran"
                :options="['tunai' => 'Tunai', 'transfer' => 'Transfer', 'qris' => 'QRIS', 'lainnya' => 'Lainnya', 'tempo' => 'Hutang (Bayar Nanti)']"
                :selected="old('metode_pembayaran', 'tunai')"
                :placeholder="null"
                x-model="paymentMethod"
                required
            />
            <x-ui.input name="diskon" label="Diskon (rupiah)" type="number" min="0" step="1" :value="old('diskon', 0)" x-model.number="discount" />
            <div x-cloak x-show="paymentMethod === 'tempo'" class="grid gap-4 rounded-md border border-amber-200 bg-amber-50 p-4 sm:col-span-2 sm:grid-cols-2">
                <x-ui.input name="bayar" label="Uang muka / DP (rupiah)" type="number" min="0" step="1" x-model.number="paidAmount" ::max="total" ::required="paymentMethod === 'tempo'" />
                <x-ui.select name="metode_dp" label="Metode pembayaran DP" :options="['tunai' => 'Tunai', 'transfer' => 'Transfer']" :selected="old('metode_dp', 'tunai')" :placeholder="null" />
                <x-ui.input name="jatuh_tempo" label="Jatuh tempo" type="date" :value="old('jatuh_tempo', $jatuhTempoDefault)" ::required="paymentMethod === 'tempo'" />
                <div class="sm:col-span-2 flex flex-wrap justify-between gap-2 text-sm">
                    <span>Total: <strong x-text="formatRupiah(total)"></strong></span>
                    <span>DP: <strong x-text="formatRupiah(paidAmount)"></strong></span>
                    <span>Sisa hutang: <strong class="text-red-700" x-text="formatRupiah(remaining)"></strong></span>
                </div>
            </div>
            <div class="sm:col-span-2">
                <label for="catatan" class="mb-1 block text-sm font-medium text-slate-700">Catatan <span class="font-normal text-slate-500">(opsional)</span></label>
                <textarea id="catatan" name="catatan" rows="2" maxlength="2000" class="w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('catatan') }}</textarea>
                @error('catatan')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700 sm:col-span-2">
                <input type="checkbox" name="perbarui_harga_beli" value="1" x-model="updatePurchasePrice" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                Perbarui harga beli produk sesuai harga item pembelian
            </label>
        </section>

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <h2 class="font-semibold">Item pembelian</h2>
                <button type="button" @click="addItem()" :disabled="products.length === 0 || items.length >= 50" class="rounded-md border border-indigo-200 px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50 disabled:cursor-not-allowed disabled:opacity-50">Tambah item</button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="min-w-64 px-5 py-3">Produk</th>
                            <th class="w-32 px-5 py-3">Qty</th>
                            <th class="w-48 px-5 py-3">Harga beli</th>
                            <th class="w-44 px-5 py-3 text-right">Subtotal</th>
                            <th class="w-20 px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(item, index) in items" :key="item.key">
                            <tr>
                                <td class="px-5 py-3">
                                    <select
                                        x-model.number="item.product_id"
                                        :name="`items[${index}][product_id]`"
                                        @change="setProductPrice(item)"
                                        required
                                        class="w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="">Pilih produk</option>
                                        <template x-for="product in products" :key="product.id">
                                            <option :value="product.id" x-text="`${product.nama} (${product.sku}) — stok ${product.stok}`"></option>
                                        </template>
                                    </select>
                                </td>
                                <td class="px-5 py-3">
                                    <input x-model.number="item.qty" :name="`items[${index}][qty]`" type="number" min="1" step="1" required class="w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </td>
                                <td class="px-5 py-3">
                                    <input x-model.number="item.harga_beli" :name="`items[${index}][harga_beli]`" type="number" min="0" step="1" required class="w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 text-right" x-text="formatRupiah(lineSubtotal(item))"></td>
                                <td class="px-5 py-3 text-right">
                                    <button type="button" @click="removeItem(index)" :disabled="items.length === 1" aria-label="Hapus item" class="rounded px-2 py-1 text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-30">&times;</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot class="border-t border-slate-200 bg-slate-50">
                        <tr>
                            <th colspan="3" class="px-5 py-3 text-right">Subtotal</th>
                            <th class="whitespace-nowrap px-5 py-3 text-right" x-text="formatRupiah(subtotal)"></th>
                            <th></th>
                        </tr>
                        <tr>
                            <th colspan="3" class="px-5 py-3 text-right">Total setelah diskon</th>
                            <th class="whitespace-nowrap px-5 py-3 text-right text-base font-bold" x-text="formatRupiah(total)"></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>

        <div class="flex justify-end gap-3">
            <a href="{{ route('purchases.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold hover:bg-white">Batal</a>
            <x-ui.button type="submit" :disabled="$suppliers->isEmpty() || $products->isEmpty()">Simpan pembelian</x-ui.button>
        </div>
    </form>
@endsection
