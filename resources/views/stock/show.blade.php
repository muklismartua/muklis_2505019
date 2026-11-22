@extends('layouts.app')

@section('title', 'Kartu Stok '.$product->nama)

@section('content')
    <div class="mb-6">
        <a href="{{ route('stock.index') }}" class="text-sm font-medium text-indigo-700 hover:underline">&larr; Kembali ke kartu stok</a>
        <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">{{ $product->nama }}</h1>
                <p class="mt-1 text-sm text-slate-600">{{ $product->sku }} · Stok saat ini: {{ number_format($product->stok, 0, ',', '.') }}</p>
            </div>
            @can('stok.kelola')
                <a href="{{ route('stock-adjustments.create', ['product_id' => $product->id]) }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Buat stok opname</a>
            @endcan
        </div>
    </div>

    <form method="GET" action="{{ route('stock.show', $product) }}" class="mb-5 grid gap-3 rounded-lg border border-slate-200 bg-white p-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
        <x-ui.input name="dari" label="Dari tanggal" type="date" :value="request('dari')" />
        <x-ui.input name="sampai" label="Sampai tanggal" type="date" :value="request('sampai')" />
        <div class="flex gap-2">
            <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>
            <a href="{{ route('stock.show', $product) }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold hover:bg-slate-50">Reset</a>
        </div>
    </form>

    <x-ui.table :headers="['Tanggal', 'Tipe', 'Qty', 'Stok sebelum', 'Stok sesudah', 'Referensi', 'Pengguna', 'Keterangan']" :paginator="$movements">
        @forelse ($movements as $movement)
            <tr>
                <td class="whitespace-nowrap px-5 py-4">{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                <td class="whitespace-nowrap px-5 py-4">
                    <x-ui.badge :status="$movement->tipe === 'masuk' ? 'active' : ($movement->tipe === 'keluar' ? 'inactive' : 'warning')">
                        {{ ucfirst($movement->tipe) }}
                    </x-ui.badge>
                </td>
                <td class="whitespace-nowrap px-5 py-4 text-right">{{ number_format($movement->qty, 0, ',', '.') }}</td>
                <td class="whitespace-nowrap px-5 py-4 text-right">{{ number_format($movement->stok_sebelum, 0, ',', '.') }}</td>
                <td class="whitespace-nowrap px-5 py-4 text-right">{{ number_format($movement->stok_sesudah, 0, ',', '.') }}</td>
                <td class="max-w-48 px-5 py-4">
                    @if ($movement->referensi)
                        {{ $movement->referensi->transaction_number ?? (class_basename($movement->referensi_type).' #'.$movement->referensi_id) }}
                    @else
                        —
                    @endif
                </td>
                <td class="whitespace-nowrap px-5 py-4">{{ $movement->user?->name ?? '—' }}</td>
                <td class="max-w-64 px-5 py-4">{{ $movement->keterangan ?: '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="8" class="px-5 py-10 text-center text-slate-500">Belum ada riwayat pergerakan untuk rentang tanggal ini.</td></tr>
        @endforelse
    </x-ui.table>
@endsection
