@extends('layouts.app')

@section('title', 'Detail Hutang Supplier')

@section('content')
    <div class="mb-6">
        <a href="{{ route('supplier-debts.index') }}" class="text-sm font-medium text-indigo-700 hover:underline">&larr; Kembali ke hutang supplier</a>
        <h1 class="mt-3 text-2xl font-bold">Hutang {{ $hutangSupplier->supplier->nama }}</h1>
        <p class="mt-1 text-sm text-slate-600">
            Pembelian <a href="{{ route('purchases.show', $hutangSupplier->purchase) }}" class="font-medium text-indigo-700 hover:underline">{{ $hutangSupplier->purchase->nomor }}</a>
            · Jatuh tempo {{ $hutangSupplier->jatuh_tempo?->format('d/m/Y') ?? '—' }}
        </p>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <section class="rounded-lg border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">Total hutang</p><p class="mt-2 text-xl font-bold">{{ rupiah($hutangSupplier->total) }}</p></section>
        <section class="rounded-lg border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">Terbayar</p><p class="mt-2 text-xl font-bold text-emerald-700">{{ rupiah($hutangSupplier->terbayar) }}</p></section>
        <section class="rounded-lg border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">Sisa hutang</p><p class="mt-2 text-xl font-bold text-red-700">{{ rupiah($hutangSupplier->sisa) }}</p></section>
    </div>

    <section class="mt-6 overflow-hidden rounded-lg border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="font-semibold">Item pembelian</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-5 py-3">Produk</th><th class="px-5 py-3 text-right">Qty</th><th class="px-5 py-3 text-right">Subtotal</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($hutangSupplier->purchase->items as $item)
                        <tr>
                            <td class="px-5 py-3">{{ $item->product?->nama ?? 'Produk dihapus' }}</td>
                            <td class="px-5 py-3 text-right">{{ number_format($item->qty, 0, ',', '.') }}</td>
                            <td class="px-5 py-3 text-right">{{ rupiah($item->subtotal) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    @if ($hutangSupplier->status === 'belum_lunas')
        @can('hutang.kelola')
            <section class="mt-6 rounded-lg border border-slate-200 bg-white p-5">
                <h2 class="font-semibold">Catat pembayaran</h2>
                <form method="POST" action="{{ route('supplier-debts.payments.store', $hutangSupplier) }}" class="mt-4 grid gap-4 sm:grid-cols-3">
                    @csrf
                    <x-ui.input name="jumlah" label="Jumlah pembayaran (rupiah)" type="number" min="1" :max="$hutangSupplier->sisa" step="1" :value="old('jumlah')" required />
                    <x-ui.select name="metode" label="Metode" :options="['tunai' => 'Tunai', 'transfer' => 'Transfer']" :selected="old('metode', 'tunai')" :placeholder="null" required />
                    <x-ui.input name="catatan" label="Catatan (opsional)" :value="old('catatan')" />
                    <div class="sm:col-span-3"><x-ui.button type="submit">Simpan pembayaran</x-ui.button></div>
                </form>
            </section>
        @endcan
    @endif

    <section class="mt-6 overflow-hidden rounded-lg border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-5 py-4"><h2 class="font-semibold">Riwayat pembayaran</h2></div>
        <x-ui.table :headers="['Nomor', 'Tanggal', 'Metode', 'Catatan', 'Dicatat oleh', 'Jumlah']">
            @forelse ($hutangSupplier->payments as $payment)
                <tr>
                    <td class="whitespace-nowrap px-5 py-4 font-medium">{{ $payment->nomor }}</td>
                    <td class="whitespace-nowrap px-5 py-4">{{ $payment->tanggal->format('d/m/Y') }}</td>
                    <td class="whitespace-nowrap px-5 py-4">{{ ucfirst($payment->metode) }}</td>
                    <td class="px-5 py-4">{{ $payment->catatan ?: '—' }}</td>
                    <td class="whitespace-nowrap px-5 py-4">{{ $payment->user?->name ?? '—' }}</td>
                    <td class="whitespace-nowrap px-5 py-4 text-right font-semibold">{{ rupiah($payment->jumlah) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">Belum ada pembayaran cicilan.</td></tr>
            @endforelse
        </x-ui.table>
    </section>
@endsection
