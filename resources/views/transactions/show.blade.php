@extends('layouts.transaction')

@section('title', 'Detail Transaksi')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('transactions.index') }}" class="text-sm font-medium text-indigo-700 hover:underline">&larr; Kembali ke transaksi</a>
            <h1 class="mt-3 text-2xl font-bold">{{ $transaction->transaction_number }}</h1>
            <p class="mt-1 text-sm text-slate-600">{{ ucfirst($transaction->type) }} pada {{ $transaction->transaction_date->format('d/m/Y') }}</p>
        </div>
        <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-700">{{ ucfirst($transaction->type) }}</span>
    </div>

    <section class="overflow-hidden rounded-lg border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Produk</th>
                        <th class="px-5 py-3 text-right">Harga satuan</th>
                        <th class="px-5 py-3 text-right">Jumlah</th>
                        <th class="px-5 py-3 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($transaction->items as $item)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="font-medium">{{ $item->product_name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $item->product_sku }}</p>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                            <td class="px-5 py-4 text-right">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t border-slate-200 bg-slate-50">
                    <tr>
                        <th colspan="3" class="px-5 py-4 text-right">Total transaksi</th>
                        <th class="whitespace-nowrap px-5 py-4 text-right">Rp {{ number_format($transaction->total, 0, ',', '.') }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>

    <dl class="mt-5 grid gap-4 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-2">
        <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Dibuat oleh</dt>
            <dd class="mt-1 text-sm">{{ $transaction->user?->name ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Catatan</dt>
            <dd class="mt-1 whitespace-pre-line text-sm">{{ $transaction->notes ?: '—' }}</dd>
        </div>
    </dl>
@endsection
