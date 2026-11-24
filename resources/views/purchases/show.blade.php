@extends('layouts.app')

@section('title', 'Detail Pembelian')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('purchases.index') }}" class="text-sm font-medium text-indigo-700 hover:underline">&larr; Kembali ke pembelian</a>
            <h1 class="mt-3 text-2xl font-bold">{{ $purchase->nomor }}</h1>
            <p class="mt-1 text-sm text-slate-600">Tanggal {{ $purchase->tanggal->format('d/m/Y') }} · {{ $purchase->supplier->nama }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('purchases.pdf', $purchase) }}" class="rounded-md border border-indigo-200 px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">Cetak/Unduh PDF</a>
            @if ($purchase->hutangSupplier)
                @canany(['hutang.lihat', 'hutang.kelola'])
                    <a href="{{ route('supplier-debts.show', $purchase->hutangSupplier) }}" class="rounded-md border border-amber-300 px-3 py-2 text-sm font-semibold text-amber-800 hover:bg-amber-50">Lihat Hutang Supplier</a>
                @endcanany
            @endif
            <x-ui.badge :status="$purchase->status === 'selesai' ? 'success' : 'inactive'">{{ ucfirst($purchase->status) }}</x-ui.badge>
            @if ($purchase->status === 'selesai')
                @can('pembelian.kelola')
                    @php($formId = 'cancel-purchase-'.$purchase->id)
                    <form id="{{ $formId }}" method="POST" action="{{ route('purchases.cancel', $purchase) }}">
                        @csrf
                        @method('PATCH')
                    </form>
                    <x-ui.confirm-modal :form-id="$formId" :id="$formId.'-title'" title="Batalkan pembelian?" confirm-text="Batalkan">
                        <x-slot:trigger><button type="button" class="rounded-md border border-red-300 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Batalkan</button></x-slot:trigger>
                        Stok dari item pembelian akan dikurangi kembali. Pembatalan gagal jika stok tidak mencukupi atau jika DP/pembayaran hutang sudah tercatat.
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
                        <th class="px-5 py-3 text-right">Harga beli</th>
                        <th class="px-5 py-3 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($purchase->items as $item)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="font-medium">{{ $item->product?->nama ?? 'Produk dihapus' }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $item->product?->sku ?? '—' }}</p>
                            </td>
                            <td class="px-5 py-4 text-right">{{ number_format($item->qty, 0, ',', '.') }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">Rp {{ number_format($item->harga_beli, 0, ',', '.') }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <dl class="grid gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 text-sm sm:grid-cols-2">
            <div><dt class="text-slate-500">Metode pembayaran</dt><dd class="font-medium">{{ $purchase->metode_pembayaran === 'tempo' ? 'Hutang (Bayar Nanti)' : ucfirst($purchase->metode_pembayaran) }}</dd></div>
            <div><dt class="text-slate-500">Status pembayaran</dt><dd class="font-medium">{{ $purchase->status_pembayaran === 'lunas' ? 'Lunas' : 'Belum lunas' }}</dd></div>
            <div><dt class="text-slate-500">Dibuat oleh</dt><dd class="font-medium">{{ $purchase->user?->name ?? '—' }}</dd></div>
            <div><dt class="text-slate-500">Subtotal</dt><dd class="font-medium">Rp {{ number_format($purchase->subtotal, 0, ',', '.') }}</dd></div>
            <div><dt class="text-slate-500">Diskon</dt><dd class="font-medium">Rp {{ number_format($purchase->diskon, 0, ',', '.') }}</dd></div>
            <div><dt class="text-slate-500">Total</dt><dd class="text-base font-bold">Rp {{ number_format($purchase->total, 0, ',', '.') }}</dd></div>
            <div><dt class="text-slate-500">Total dibayar</dt><dd class="font-medium">{{ rupiah($purchase->total_dibayar) }}</dd></div>
            <div><dt class="text-slate-500">Sisa hutang</dt><dd class="font-medium">{{ rupiah($purchase->sisa) }}</dd></div>
            @if ($purchase->jatuh_tempo)
                <div><dt class="text-slate-500">Jatuh tempo</dt><dd class="font-medium">{{ $purchase->jatuh_tempo->format('d/m/Y') }}</dd></div>
            @endif
            <div><dt class="text-slate-500">Catatan</dt><dd class="whitespace-pre-line font-medium">{{ $purchase->catatan ?: '—' }}</dd></div>
        </dl>
    </section>
@endsection
