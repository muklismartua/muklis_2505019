@extends('layouts.app')

@section('title', 'Detail Penjualan')

@section('content')
    @php($totalDibayar = $sale->status_pembayaran === 'lunas' && $sale->total_dibayar === 0 ? min($sale->bayar, $sale->total) : $sale->total_dibayar)
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('sales.index') }}" class="text-sm font-medium text-indigo-700 hover:underline">&larr; Kembali ke penjualan</a>
            <h1 class="mt-3 text-2xl font-bold">{{ $sale->nomor }}</h1>
            <p class="mt-1 text-sm text-slate-600">Tanggal {{ $sale->tanggal->format('d/m/Y') }} · {{ $sale->customer?->nama ?? 'Pelanggan umum' }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('sales.pdf', $sale) }}" class="rounded-md border border-indigo-200 px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">Cetak/Unduh PDF</a>
            <a href="{{ route('sales.receipt', $sale) }}" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cetak Nota (80 mm)</a>
            @if ($sale->status === 'selesai' && $sale->piutang && $sale->sisa > 0)
                @can('piutang.kelola')
                    <a href="{{ route('piutangs.show', $sale->piutang) }}" class="rounded-md border border-emerald-200 px-3 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50">Catat Pembayaran</a>
                @endcan
            @endif
            <x-ui.badge :status="$sale->status === 'selesai' ? 'success' : 'inactive'">{{ ucfirst($sale->status) }}</x-ui.badge>
            @if ($sale->status === 'selesai')
                @can('penjualan.kelola')
                    @php($formId = 'cancel-sale-'.$sale->id)
                    <form id="{{ $formId }}" method="POST" action="{{ route('sales.cancel', $sale) }}">
                        @csrf
                        @method('PATCH')
                    </form>
                    <x-ui.confirm-modal :form-id="$formId" :id="$formId.'-title'" title="Batalkan penjualan?" confirm-text="Batalkan">
                        <x-slot:trigger><button type="button" class="rounded-md border border-red-300 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Batalkan</button></x-slot:trigger>
                        Stok dari seluruh item penjualan akan dikembalikan. Pembatalan tidak dapat diulang.
                    </x-ui.confirm-modal>
                @endcan
            @endif
        </div>
    </div>

    <section class="overflow-hidden rounded-lg border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Produk</th>
                        <th class="px-5 py-3 text-right">Qty</th>
                        <th class="px-5 py-3 text-right">Harga jual</th>
                        <th class="px-5 py-3 text-right">Diskon item</th>
                        <th class="px-5 py-3 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($sale->items as $item)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="font-medium">{{ $item->product?->nama ?? 'Produk dihapus' }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $item->product?->sku ?? '—' }}</p>
                            </td>
                            <td class="px-5 py-4 text-right">{{ number_format($item->qty, 0, ',', '.') }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">Rp {{ number_format($item->harga_jual, 0, ',', '.') }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">Rp {{ number_format($item->diskon, 0, ',', '.') }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <dl class="grid gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 text-sm sm:grid-cols-2">
            <div><dt class="text-slate-500">Metode pembayaran</dt><dd class="font-medium">{{ $sale->metode_pembayaran === 'tempo' ? 'Hutang (Bayar Nanti)' : ucfirst($sale->metode_pembayaran) }}</dd></div>
            <div>
                <dt class="text-slate-500">Status pembayaran</dt>
                @if ($sale->status === 'batal')
                    <dd class="font-medium text-slate-600">Transaksi dibatalkan</dd>
                @elseif ($sale->sisa > 0)
                    <dd class="font-semibold text-amber-700">Belum lunas · Sisa Rp {{ number_format($sale->sisa, 0, ',', '.') }}</dd>
                @else
                    <dd class="font-semibold text-emerald-700">Lunas</dd>
                @endif
            </div>
            @if ($sale->jatuh_tempo)
                <div><dt class="text-slate-500">Jatuh tempo</dt><dd class="font-medium">{{ $sale->jatuh_tempo->format('d/m/Y') }}</dd></div>
            @endif
            <div><dt class="text-slate-500">Dibuat oleh</dt><dd class="font-medium">{{ $sale->user?->name ?? '—' }}</dd></div>
            <div><dt class="text-slate-500">Subtotal item</dt><dd class="font-medium">Rp {{ number_format($sale->subtotal, 0, ',', '.') }}</dd></div>
            <div><dt class="text-slate-500">Diskon transaksi</dt><dd class="font-medium">Rp {{ number_format($sale->diskon, 0, ',', '.') }}</dd></div>
            <div><dt class="text-slate-500">Pajak</dt><dd class="font-medium">Rp {{ number_format($sale->pajak, 0, ',', '.') }}</dd></div>
            <div><dt class="text-slate-500">Total</dt><dd class="text-base font-bold">Rp {{ number_format($sale->total, 0, ',', '.') }}</dd></div>
            <div><dt class="text-slate-500">Bayar</dt><dd class="font-medium">Rp {{ number_format($sale->bayar, 0, ',', '.') }}</dd></div>
            <div><dt class="text-slate-500">Total dibayar</dt><dd class="font-medium">Rp {{ number_format($totalDibayar, 0, ',', '.') }}</dd></div>
            <div><dt class="text-slate-500">Kembalian</dt><dd class="font-medium">Rp {{ number_format($sale->kembalian, 0, ',', '.') }}</dd></div>
            @if ($sale->piutang && $sale->sisa > 0)
                <div><dt class="text-slate-500">Sisa piutang</dt><dd class="font-semibold text-amber-700">Rp {{ number_format($sale->sisa, 0, ',', '.') }} ({{ str_replace('_', ' ', ucfirst($sale->piutang->status)) }})</dd></div>
            @endif
            <div class="sm:col-span-2"><dt class="text-slate-500">Catatan</dt><dd class="whitespace-pre-line font-medium">{{ $sale->catatan ?: '—' }}</dd></div>
        </dl>
    </section>
@endsection
