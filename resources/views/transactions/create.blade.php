@extends('layouts.app')

@section('title', 'Buat Transaksi')

@section('content')
    <div class="mb-6">
        <a href="{{ route('transactions.index') }}" class="text-sm font-medium text-indigo-700 hover:underline">&larr; Kembali ke transaksi</a>
        <h1 class="mt-3 text-2xl font-bold">Buat transaksi</h1>
        <p class="mt-1 text-sm text-slate-600">Harga diambil dari data produk saat transaksi disimpan.</p>
    </div>

    @if ($products->isEmpty())
        <div class="mb-5 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            Belum ada produk aktif. Tambahkan produk terlebih dahulu pada tahap master data.
        </div>
    @endif

    <form id="transaction-form" data-type="{{ old('type', $defaultType) }}" method="POST" action="{{ route('transactions.store') }}" class="space-y-6">
        @csrf
        <section class="grid gap-4 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-2">
            <div>
                <label for="type" class="mb-1 block text-sm font-medium">Jenis transaksi</label>
                <select id="type" name="type" required class="w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @if ($canCreateSales)
                        <option value="penjualan" @selected(old('type', $defaultType) === 'penjualan')>Penjualan (stok keluar)</option>
                    @endif
                    @if ($canCreatePurchases)
                        <option value="pembelian" @selected(old('type', $defaultType) === 'pembelian')>Pembelian (stok masuk)</option>
                    @endif
                </select>
            </div>
            <div>
                <label for="transaction_date" class="mb-1 block text-sm font-medium">Tanggal transaksi</label>
                <input id="transaction_date" name="transaction_date" type="date" required value="{{ old('transaction_date', now()->toDateString()) }}" class="w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div id="supplier-field" class="hidden">
                <label for="supplier_id" class="mb-1 block text-sm font-medium">Supplier <span class="font-normal text-slate-500">(opsional)</span></label>
                <select id="supplier_id" name="supplier_id" class="w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Pilih supplier</option>
                    @foreach ($suppliers as $id => $name)
                        <option value="{{ $id }}" @selected((string) old('supplier_id') === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('supplier_id')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div id="customer-field" class="hidden">
                <label for="customer_id" class="mb-1 block text-sm font-medium">Pelanggan <span class="font-normal text-slate-500">(opsional)</span></label>
                <select id="customer_id" name="customer_id" class="w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Pilih pelanggan</option>
                    @foreach ($customers as $id => $name)
                        <option value="{{ $id }}" @selected((string) old('customer_id') === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('customer_id')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label for="notes" class="mb-1 block text-sm font-medium">Catatan <span class="font-normal text-slate-500">(opsional)</span></label>
                <textarea id="notes" name="notes" rows="2" maxlength="2000" class="w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes') }}</textarea>
            </div>
        </section>

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <h2 class="font-semibold">Item transaksi</h2>
                <button id="add-item" type="button" class="rounded-md border border-indigo-200 px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50" @disabled($products->isEmpty())>Tambah item</button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Produk</th>
                            <th class="w-32 px-5 py-3">Jumlah</th>
                            <th class="w-44 px-5 py-3 text-right">Subtotal</th>
                            <th class="w-20 px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody id="item-rows" class="divide-y divide-slate-100">
                        @foreach (old('items', [['product_id' => '', 'quantity' => 1]]) as $index => $oldItem)
                            <tr class="item-row">
                                <td class="min-w-64 px-5 py-3">
                                    <select name="items[{{ $index }}][product_id]" required class="product-select w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" @disabled($products->isEmpty())>
                                        <option value="">Pilih produk</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}"
                                                data-sale-price="{{ $product->harga_jual }}"
                                                data-purchase-price="{{ $product->harga_beli }}"
                                                @selected((string) ($oldItem['product_id'] ?? '') === (string) $product->id)>
                                                {{ $product->nama }} ({{ $product->sku }}) — stok {{ $product->stok }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="unit-price mt-1 text-xs text-slate-500"></p>
                                </td>
                                <td class="px-5 py-3">
                                    <input name="items[{{ $index }}][quantity]" type="number" min="1" max="1000000" step="1" required value="{{ $oldItem['quantity'] ?? 1 }}" class="quantity-input w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </td>
                                <td class="row-subtotal whitespace-nowrap px-5 py-3 text-right">Rp 0</td>
                                <td class="px-5 py-3 text-right">
                                    <button type="button" class="remove-item rounded px-2 py-1 text-sm text-red-700 hover:bg-red-50" aria-label="Hapus item">&times;</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t border-slate-200 bg-slate-50">
                        <tr>
                            <th colspan="2" class="px-5 py-4 text-right">Total</th>
                            <th id="transaction-total" class="whitespace-nowrap px-5 py-4 text-right text-base">Rp 0</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>

        <div class="flex justify-end gap-3">
            <a href="{{ route('transactions.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold hover:bg-white">Batal</a>
            <button type="submit" class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700" @disabled($products->isEmpty())>Simpan transaksi</button>
        </div>
    </form>

    <template id="item-row-template">
        <tr class="item-row">
            <td class="min-w-64 px-5 py-3">
                <select required class="product-select w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Pilih produk</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" data-sale-price="{{ $product->harga_jual }}" data-purchase-price="{{ $product->harga_beli }}">
                            {{ $product->nama }} ({{ $product->sku }}) — stok {{ $product->stok }}
                        </option>
                    @endforeach
                </select>
                <p class="unit-price mt-1 text-xs text-slate-500"></p>
            </td>
            <td class="px-5 py-3">
                <input type="number" min="1" max="1000000" step="1" required value="1" class="quantity-input w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </td>
            <td class="row-subtotal whitespace-nowrap px-5 py-3 text-right">Rp 0</td>
            <td class="px-5 py-3 text-right">
                <button type="button" class="remove-item rounded px-2 py-1 text-sm text-red-700 hover:bg-red-50" aria-label="Hapus item">&times;</button>
            </td>
        </tr>
    </template>
@endsection
