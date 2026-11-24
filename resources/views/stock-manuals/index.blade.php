@extends('layouts.app')

@section('title', 'Riwayat Stok Manual')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold">Riwayat Stok Manual</h1>
            <p class="mt-1 text-sm text-slate-600">Daftar penambahan dan pengurangan stok dengan alasan dan pengguna.</p>
        </div>
        @can('stok.kelola')
            <a href="{{ route('stock-manuals.create') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Tambah/Kurang Stok</a>
        @endcan
    </div>

    <form method="GET" action="{{ route('stock-manuals.index') }}" class="mb-5 grid gap-4 rounded-lg border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-6">
        <x-ui.input name="dari" label="Dari tanggal" type="date" :value="$filters['dari'] ?? ''" />
        <x-ui.input name="sampai" label="Sampai tanggal" type="date" :value="$filters['sampai'] ?? ''" />
        <x-ui.select name="tipe" label="Tipe" :options="['tambah' => 'Tambah', 'kurang' => 'Kurang']" :selected="$filters['tipe'] ?? null" placeholder="Semua tipe" />
        <x-ui.select name="alasan" label="Alasan" :options="$reasons" :selected="$filters['alasan'] ?? null" placeholder="Semua alasan" />
        <x-ui.select name="product_id" label="Produk" :options="$products" :selected="$filters['product_id'] ?? null" placeholder="Semua produk" />
        <div class="flex items-end gap-2">
            <x-ui.button type="submit">Filter</x-ui.button>
            <a href="{{ route('stock-manuals.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold hover:bg-slate-50">Reset</a>
        </div>
    </form>

    <x-ui.table :headers="['Nomor', 'Tanggal', 'Produk', 'Tipe', 'Qty', 'Alasan', 'Keterangan', 'Pengguna']" :paginator="$manuals">
        @forelse ($manuals as $manual)
            <tr>
                <td class="whitespace-nowrap px-5 py-4 font-medium">{{ $manual->nomor }}</td>
                <td class="whitespace-nowrap px-5 py-4">{{ $manual->tanggal->format('d/m/Y') }}</td>
                <td class="whitespace-nowrap px-5 py-4">
                    <a href="{{ route('stock.show', $manual->product) }}" class="font-medium text-indigo-700 hover:underline">{{ $manual->product->nama }}</a>
                    <p class="mt-1 text-xs text-slate-500">{{ $manual->product->sku }}</p>
                </td>
                <td class="whitespace-nowrap px-5 py-4">
                    <x-ui.badge :status="$manual->tipe === 'tambah' ? 'active' : 'inactive'">{{ ucfirst($manual->tipe) }}</x-ui.badge>
                </td>
                <td class="whitespace-nowrap px-5 py-4 text-right">{{ number_format($manual->qty, 0, ',', '.') }}</td>
                <td class="whitespace-nowrap px-5 py-4">{{ $manual->alasan_label }}</td>
                <td class="max-w-64 px-5 py-4">{{ $manual->keterangan ?: '—' }}</td>
                <td class="whitespace-nowrap px-5 py-4">{{ $manual->user?->name ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="8" class="px-5 py-10 text-center text-slate-500">Belum ada riwayat stok manual.</td></tr>
        @endforelse
    </x-ui.table>
@endsection
