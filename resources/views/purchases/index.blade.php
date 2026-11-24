@extends('layouts.app')

@section('title', 'Pembelian')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold">Pembelian</h1>
            <p class="mt-1 text-sm text-slate-600">Riwayat pembelian barang masuk.</p>
        </div>
        @can('pembelian.kelola')
            <a href="{{ route('purchases.create') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Buat pembelian</a>
        @endcan
    </div>

    <form method="GET" action="{{ route('purchases.index') }}" class="mb-5 grid gap-3 rounded-lg border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-5">
        <x-ui.input name="dari" label="Dari tanggal" type="date" :value="request('dari')" />
        <x-ui.input name="sampai" label="Sampai tanggal" type="date" :value="request('sampai')" />
        <x-ui.select name="supplier_id" label="Supplier" :options="$suppliers" :selected="request('supplier_id')" placeholder="Semua supplier" />
        <x-ui.select name="status" label="Status" :options="['selesai' => 'Selesai', 'batal' => 'Batal']" :selected="request('status')" placeholder="Semua status" />
        <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>
    </form>

    <x-ui.table :headers="['Nomor', 'Tanggal', 'Supplier', 'Metode pembayaran', 'Status pembayaran', 'Sisa', 'Total', 'Status']" :paginator="$purchases">
        @forelse ($purchases as $purchase)
            <tr>
                <td class="whitespace-nowrap px-5 py-4 font-medium">
                    <a href="{{ route('purchases.show', $purchase) }}" class="text-indigo-700 hover:underline">{{ $purchase->nomor }}</a>
                </td>
                <td class="whitespace-nowrap px-5 py-4">{{ $purchase->tanggal->format('d/m/Y') }}</td>
                <td class="whitespace-nowrap px-5 py-4">{{ $purchase->supplier->nama }}</td>
                <td class="whitespace-nowrap px-5 py-4">{{ $purchase->metode_pembayaran === 'tempo' ? 'Hutang (Bayar Nanti)' : ucfirst($purchase->metode_pembayaran) }}</td>
                <td class="whitespace-nowrap px-5 py-4">
                    <x-ui.badge :status="$purchase->status_pembayaran === 'lunas' ? 'success' : 'warning'">{{ $purchase->status_pembayaran === 'lunas' ? 'Lunas' : 'Belum lunas' }}</x-ui.badge>
                </td>
                <td class="whitespace-nowrap px-5 py-4 text-right">{{ rupiah($purchase->sisa) }}</td>
                <td class="whitespace-nowrap px-5 py-4 text-right">Rp {{ number_format($purchase->total, 0, ',', '.') }}</td>
                <td class="whitespace-nowrap px-5 py-4">
                    <x-ui.badge :status="$purchase->status === 'selesai' ? 'success' : 'inactive'">{{ ucfirst($purchase->status) }}</x-ui.badge>
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="px-5 py-10 text-center text-slate-500">Belum ada pembelian.</td></tr>
        @endforelse
    </x-ui.table>
@endsection
