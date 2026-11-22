@extends('layouts.app')

@section('title', 'Daftar Transaksi')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold">Transaksi</h1>
            <p class="mt-1 text-sm text-slate-600">Riwayat penjualan dan pembelian stok.</p>
        </div>
        @canany(['penjualan.kelola', 'pembelian.kelola'])
            <a href="{{ route('transactions.create') }}" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                Buat transaksi
            </a>
        @endcanany
    </div>

    <form method="GET" action="{{ route('transactions.index') }}" class="mb-5 flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-4 sm:flex-row">
        <label class="sr-only" for="q">Cari nomor transaksi atau catatan</label>
        <input id="q" name="q" value="{{ request('q') }}" placeholder="Cari nomor transaksi atau catatan..." class="min-w-0 flex-1 rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
        <label class="sr-only" for="type">Jenis transaksi</label>
        <select id="type" name="type" class="rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Semua jenis</option>
            @can('penjualan.lihat')
                <option value="penjualan" @selected(request('type') === 'penjualan')>Penjualan</option>
            @endcan
            @can('pembelian.lihat')
                <option value="pembelian" @selected(request('type') === 'pembelian')>Pembelian</option>
            @endcan
        </select>
        <button class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50">Cari</button>
    </form>

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Nomor</th>
                        <th class="px-5 py-3">Tanggal</th>
                        <th class="px-5 py-3">Jenis</th>
                        <th class="px-5 py-3">Dibuat oleh</th>
                        <th class="px-5 py-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($transactions as $transaction)
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-5 py-4 font-medium">
                                <a href="{{ route('transactions.show', $transaction) }}" class="text-indigo-700 hover:underline">{{ $transaction->transaction_number }}</a>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4">{{ $transaction->transaction_date->format('d/m/Y') }}</td>
                            <td class="whitespace-nowrap px-5 py-4">{{ ucfirst($transaction->type) }}</td>
                            <td class="whitespace-nowrap px-5 py-4">{{ $transaction->user?->name ?? '—' }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">Rp {{ number_format($transaction->total, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">Belum ada transaksi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($transactions->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">{{ $transactions->links() }}</div>
        @endif
    </div>
@endsection
