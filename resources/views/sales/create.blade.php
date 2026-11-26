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
    $initialCustomer = $selectedCustomer ? [
        'id' => $selectedCustomer->id,
        'kode' => $selectedCustomer->kode,
        'nama' => $selectedCustomer->nama,
        'telepon' => $selectedCustomer->telepon,
        'batas_hutang' => $selectedCustomer->batas_hutang,
        'total_hutang' => $selectedCustomer->total_hutang ?? 0,
        'sisa_batas_hutang' => $selectedCustomer->sisa_batas_hutang,
        'ada_piutang_terlambat' => (bool) ($selectedCustomer->ada_piutang_terlambat ?? false),
    ] : null;
    $initialValues = [
        'diskon' => old('diskon', 0),
        'pajak' => old('pajak', 0),
        'pajak_persen' => old('pajak_persen', $defaultTaxRate),
        'bayar' => old('bayar', 0),
        'metode_pembayaran' => old('metode_pembayaran', 'tunai'),
        'jatuh_tempo' => old('jatuh_tempo', $defaultDueDate),
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
        x-data="saleForm(@js($saleProducts), @js(old('items', [])), @js($initialValues), @js($customerSearchUrl), @js($quickCustomerUrl), @js($initialCustomer), @js(csrf_token()))"
        class="space-y-6"
    >
        @csrf

        <section class="grid gap-4 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-2">
            <div class="relative">
                <label for="customer-search" class="mb-1 block text-sm font-medium text-slate-700">Pelanggan (wajib untuk Hutang)</label>
                <input type="hidden" name="customer_id" :value="customerId">
                <div class="flex gap-2">
                    <input
                        id="customer-search"
                        type="search"
                        x-model="customerQuery"
                        @input.debounce.250ms="cariPelanggan()"
                        @focus="customerOpen = true"
                        @keydown.escape="customerOpen = false"
                        autocomplete="off"
                        placeholder="Cari nama atau nomor telepon"
                        class="min-w-0 flex-1 rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        aria-describedby="customer-selection"
                    >
                    <button
                        type="button"
                        @click="bukaModalPelanggan()"
                        class="whitespace-nowrap rounded-md border border-indigo-200 px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50"
                    >+ Pelanggan Baru</button>
                </div>
                <div
                    x-cloak
                    x-show="customerOpen"
                    @click.outside="customerOpen = false"
                    class="absolute z-30 mt-1 max-h-72 w-full overflow-y-auto rounded-md border border-slate-200 bg-white shadow-lg"
                >
                    <button
                        type="button"
                        @click="pilihPelanggan(null)"
                        :disabled="metodePembayaran === 'tempo'"
                        class="block w-full border-b border-slate-100 px-4 py-3 text-left text-sm font-medium hover:bg-slate-50"
                    >Umum / Tanpa nama <span x-show="metodePembayaran === 'tempo'" class="font-normal text-slate-500">(tidak tersedia untuk Hutang)</span></button>
                    <template x-for="customer in customerResults" :key="customer.id">
                        <button
                            type="button"
                            @click="pilihPelanggan(customer)"
                            class="block w-full border-b border-slate-100 px-4 py-3 text-left text-sm last:border-b-0 hover:bg-slate-50"
                        >
                            <span class="block font-medium" x-text="customer.nama"></span>
                            <span class="text-xs text-slate-500" x-text="`${customer.kode} · ${customer.telepon || 'Tanpa telepon'}`"></span>
                        </button>
                    </template>
                    <p x-cloak x-show="customerLoading" class="px-4 py-3 text-sm text-slate-500">Mencari pelanggan...</p>
                    <p x-cloak x-show="!customerLoading && customerQuery.trim() && customerResults.length === 0" class="px-4 py-3 text-sm text-slate-500">Pelanggan tidak ditemukan.</p>
                </div>
                <p id="customer-selection" class="mt-2 text-sm text-slate-600" aria-live="polite">
                    <span x-show="selectedCustomer" x-text="selectedCustomer ? `Dipilih: ${selectedCustomer.nama} (${selectedCustomer.kode})` : ''"></span>
                    <span x-show="!selectedCustomer">Umum / Tanpa nama</span>
                </p>
                <p x-cloak x-show="metodePembayaran === 'tempo' && !customerId" class="mt-1 text-sm text-red-700">Pilih pelanggan untuk pembayaran Hutang (Bayar Nanti).</p>
                <p x-cloak x-show="customerSelectionError" x-text="customerSelectionError" class="mt-1 text-sm text-red-700" role="alert"></p>
                <p x-cloak x-show="customerSearchError" x-text="customerSearchError" class="mt-1 text-sm text-red-700" role="alert"></p>
                @error('customer_id')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <x-ui.input name="tanggal" label="Tanggal penjualan" type="date" :value="old('tanggal', now()->toDateString())" required />
            <x-ui.select
                name="metode_pembayaran"
                label="Metode pembayaran"
                :options="['tunai' => 'Tunai', 'transfer' => 'Transfer', 'tempo' => 'Hutang (Bayar Nanti)']"
                :selected="old('metode_pembayaran', 'tunai')"
                :placeholder="null"
                x-model="metodePembayaran"
                required
            />
            <div x-cloak x-show="metodePembayaran === 'tempo'">
                <x-ui.input
                    name="jatuh_tempo"
                    label="Tanggal jatuh tempo"
                    type="date"
                    :value="old('jatuh_tempo', $defaultDueDate)"
                    x-model="jatuhTempo"
                    ::required="metodePembayaran === 'tempo'"
                />
            </div>
            <div>
                <label for="bayar" class="mb-1 block text-sm font-medium text-slate-700">
                    <span x-text="metodePembayaran === 'tempo' ? 'Uang Muka/DP (rupiah)' : 'Bayar (rupiah)'"></span>
                </label>
                <input id="bayar" name="bayar" type="number" min="0" step="1" :max="metodePembayaran === 'tempo' ? total : null" value="{{ old('bayar', 0) }}" x-model.number="bayar" required class="w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('bayar')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
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
            <div x-cloak x-show="metodePembayaran === 'tempo'" class="space-y-3 rounded-md border border-slate-200 bg-slate-50 p-4 sm:col-span-2">
                <div class="grid gap-3 text-sm sm:grid-cols-2">
                    <p>Total penjualan: <strong x-text="formatRupiah(total)"></strong></p>
                    <p>Uang muka/DP: <strong x-text="formatRupiah(Math.max(Number(bayar || 0), 0))"></strong></p>
                    <p>Sisa hutang baru: <strong x-text="formatRupiah(sisaHutangBaru)"></strong></p>
                    <p>Hutang pelanggan sebelumnya: <strong x-text="formatRupiah(totalHutangSebelumnya)"></strong></p>
                    <p>Sisa batas hutang:
                        <strong x-text="sisaBatasHutang === null ? 'Tanpa batas' : formatRupiah(sisaBatasHutang)"></strong>
                    </p>
                </div>
                <p x-cloak x-show="selectedCustomer && selectedCustomer.ada_piutang_terlambat" class="rounded-md border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900" role="status">
                    Pelanggan memiliki piutang yang telah melewati jatuh tempo. Penjualan tetap dapat dilanjutkan.
                </p>
                <p x-cloak x-show="batasHutangTerlewati" class="rounded-md border border-red-300 bg-red-50 p-3 text-sm text-red-800" role="alert">
                    Batas hutang akan terlampaui. Kurangi DP/sisa hutang atau pilih pelanggan lain sebelum menyimpan.
                </p>
                <p x-cloak x-show="metodePembayaran === 'tempo' && Number(bayar || 0) > total" class="rounded-md border border-red-300 bg-red-50 p-3 text-sm text-red-800" role="alert">
                    Uang muka/DP tidak boleh melebihi total penjualan.
                </p>
            </div>
            <p class="text-xs text-slate-500 sm:col-span-2">
                Pembayaran tunai dan transfer harus melunasi total. Hutang (Bayar Nanti) mewajibkan pelanggan dan dapat dibayar sebagian.
            </p>
        </section>

        <section class="overflow-visible rounded-lg border border-slate-200 bg-white">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-semibold">Tambah produk ke keranjang</h2>
                <div class="relative mt-3" @click.outside="productPickerOpen = false">
                    <label class="sr-only" for="cari-produk">Cari produk berdasarkan nama atau SKU</label>
                    <input
                        id="cari-produk"
                        type="search"
                        x-model="query"
                        @focus="productPickerOpen = true"
                        @keydown.escape="productPickerOpen = false"
                        autocomplete="off"
                        placeholder="Ketik nama produk atau SKU"
                        class="w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                    <button
                        type="button"
                        @click="productPickerOpen = !productPickerOpen"
                        class="mt-2 text-sm font-semibold text-indigo-700 hover:underline"
                        :aria-expanded="productPickerOpen.toString()"
                    >
                        <span x-text="productPickerOpen ? 'Tutup daftar produk' : 'Pilih produk untuk ditambahkan'"></span>
                    </button>
                    <div
                        x-cloak
                        x-show="productPickerOpen"
                        class="mt-2 max-h-64 w-full overflow-y-auto rounded-md border border-slate-200 bg-white shadow-sm"
                    >
                        <p x-show="!query.trim() && hasilPencarian.length > 0" class="border-b border-slate-100 px-4 py-2 text-xs text-slate-500">
                            Pilih produk di bawah, atau ketik nama/SKU untuk mencari.
                        </p>
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
                                <span class="flex shrink-0 items-center gap-3">
                                    <span class="whitespace-nowrap text-slate-700" x-text="formatRupiah(product.harga_jual)"></span>
                                    <span class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white" x-text="product.stok > 0 ? 'Tambah' : 'Stok habis'"></span>
                                </span>
                            </button>
                        </template>
                        <p x-show="hasilPencarian.length === 0" class="px-4 py-3 text-sm text-slate-500">
                            <span x-show="query.trim()">Produk tidak ditemukan.</span>
                            <span x-show="!query.trim()">Belum ada produk aktif yang bisa dijual.</span>
                        </p>
                    </div>
                </div>
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
            <x-ui.button type="submit" :disabled="$products->isEmpty()" ::disabled="tidakBolehSimpan">Simpan penjualan</x-ui.button>
        </div>

        <div
            x-cloak
            x-show="customerModalOpen"
            x-transition.opacity
            @keydown.escape.window="customerModalOpen = false"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
            role="presentation"
        >
            <div @click.self="customerModalOpen = false" class="absolute inset-0"></div>
            <section
                role="dialog"
                aria-modal="true"
                aria-labelledby="quick-customer-title"
                class="relative z-10 w-full max-w-lg rounded-lg bg-white p-6 shadow-xl"
            >
                <div class="mb-5 flex items-start justify-between gap-4">
                    <div>
                        <h2 id="quick-customer-title" class="text-lg font-bold">Tambah pelanggan baru</h2>
                        <p class="mt-1 text-sm text-slate-600">Pelanggan akan langsung dipilih tanpa mengosongkan keranjang.</p>
                    </div>
                    <button type="button" @click="customerModalOpen = false" aria-label="Tutup" class="rounded px-2 text-2xl leading-none text-slate-500 hover:bg-slate-100">&times;</button>
                </div>

                <div x-cloak x-show="quickCustomerMessage" class="mb-4 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900" role="alert">
                    <p x-text="quickCustomerMessage"></p>
                    <div x-cloak x-show="duplicateCustomer" class="mt-3 flex flex-wrap items-center justify-between gap-3">
                        <span x-text="duplicateCustomer ? `${duplicateCustomer.nama} · ${duplicateCustomer.telepon || 'Tanpa telepon'}` : ''"></span>
                        <button
                            type="button"
                            @click="pilihPelanggan(duplicateCustomer); customerModalOpen = false"
                            class="rounded-md bg-amber-700 px-3 py-2 text-xs font-semibold text-white hover:bg-amber-800"
                        >Gunakan pelanggan ini</button>
                    </div>
                </div>

                <div @keydown.enter.prevent class="space-y-4">
                    <div>
                        <label for="quick-customer-name" class="mb-1 block text-sm font-medium text-slate-700">Nama <span class="text-red-600">*</span></label>
                        <input id="quick-customer-name" x-model="quickCustomer.nama" type="text" maxlength="255" required :disabled="!customerModalOpen" class="w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <template x-for="message in (quickCustomerErrors.nama || [])" :key="message">
                            <p x-text="message" class="mt-1 text-sm text-red-700"></p>
                        </template>
                    </div>
                    <div>
                        <label for="quick-customer-phone" class="mb-1 block text-sm font-medium text-slate-700">Telepon</label>
                        <input id="quick-customer-phone" x-model="quickCustomer.telepon" type="tel" maxlength="50" :disabled="!customerModalOpen" class="w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <template x-for="message in (quickCustomerErrors.telepon || [])" :key="message">
                            <p x-text="message" class="mt-1 text-sm text-red-700"></p>
                        </template>
                    </div>
                    <div>
                        <label for="quick-customer-address" class="mb-1 block text-sm font-medium text-slate-700">Alamat</label>
                        <textarea id="quick-customer-address" x-model="quickCustomer.alamat" rows="2" maxlength="2000" :disabled="!customerModalOpen" class="w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        <template x-for="message in (quickCustomerErrors.alamat || [])" :key="message">
                            <p x-text="message" class="mt-1 text-sm text-red-700"></p>
                        </template>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="customerModalOpen = false" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold hover:bg-slate-50">Batal</button>
                    <button
                        type="button"
                        @click="buatPelangganCepat()"
                        :disabled="quickCustomerSubmitting"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <span x-show="!quickCustomerSubmitting">Simpan & pilih</span>
                        <span x-cloak x-show="quickCustomerSubmitting">Menyimpan...</span>
                    </button>
                </div>
            </section>
        </div>
    </form>
@endsection
