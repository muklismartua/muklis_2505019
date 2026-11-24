@extends('layouts.app')

@section('title', 'Penjualan')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold">Penjualan</h1>
            <p class="mt-1 text-sm text-slate-600">Riwayat transaksi barang keluar.</p>
        </div>
        @can('penjualan.kelola')
            <a href="{{ route('sales.create') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Buat penjualan</a>
        @endcan
    </div>

    <form method="GET" action="{{ route('sales.index') }}" class="mb-5 grid gap-3 rounded-lg border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-6">
        <x-ui.input name="dari" label="Dari tanggal" type="date" :value="request('dari')" />
        <x-ui.input name="sampai" label="Sampai tanggal" type="date" :value="request('sampai')" />
        <x-ui.select name="customer_id" label="Pelanggan" :options="$customers" :selected="request('customer_id')" placeholder="Semua pelanggan" />
        <x-ui.select name="status" label="Status" :options="['selesai' => 'Selesai', 'batal' => 'Batal']" :selected="request('status')" placeholder="Semua status" />
        <x-ui.select name="metode_pembayaran" label="Metode pembayaran" :options="['tunai' => 'Tunai', 'transfer' => 'Transfer', 'tempo' => 'Hutang (Bayar Nanti)']" :selected="request('metode_pembayaran')" placeholder="Semua metode" />
        <div class="flex items-end"><x-ui.button type="submit" variant="secondary" class="w-full">Filter</x-ui.button></div>
    </form>

    <x-ui.table :headers="['Nomor', 'Tanggal', 'Pelanggan', 'Metode pembayaran', 'Total', 'Status pembayaran', 'Status transaksi']" :paginator="$sales">
        @forelse ($sales as $sale)
            <tr>
                <td class="whitespace-nowrap px-5 py-4 font-medium">
                    <a href="{{ route('sales.show', $sale) }}" class="text-indigo-700 hover:underline">{{ $sale->nomor }}</a>
                </td>
                <td class="whitespace-nowrap px-5 py-4">{{ $sale->tanggal->format('d/m/Y') }}</td>
                <td class="whitespace-nowrap px-5 py-4">{{ $sale->customer?->nama ?? 'Pelanggan umum' }}</td>
                <td class="whitespace-nowrap px-5 py-4">{{ $sale->metode_pembayaran === 'tempo' ? 'Hutang (Bayar Nanti)' : ucfirst($sale->metode_pembayaran) }}</td>
                <td class="whitespace-nowrap px-5 py-4 text-right">Rp {{ number_format($sale->total, 0, ',', '.') }}</td>
                <td class="whitespace-nowrap px-5 py-4">
                    @if ($sale->status === 'batal')
                        <span class="text-slate-500">—</span>
                    @elseif ($sale->sisa > 0)
                        <span class="font-medium text-amber-700">Belum lunas · Sisa Rp {{ number_format($sale->sisa, 0, ',', '.') }}</span>
                    @else
                        <span class="font-medium text-emerald-700">Lunas</span>
                    @endif
                </td>
                <td class="whitespace-nowrap px-5 py-4">
                    <x-ui.badge :status="$sale->status === 'selesai' ? 'success' : 'inactive'">{{ ucfirst($sale->status) }}</x-ui.badge>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="px-5 py-10 text-center text-slate-500">Belum ada penjualan.</td></tr>
        @endforelse
    </x-ui.table>
@endsection
