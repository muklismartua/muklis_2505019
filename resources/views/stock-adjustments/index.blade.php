@extends('layouts.app')

@section('title', 'Stok Opname')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold">Stok Opname</h1>
            <p class="mt-1 text-sm text-slate-600">Riwayat pencatatan stok fisik dan selisihnya.</p>
        </div>
        @can('stok.kelola')
            <a href="{{ route('stock-adjustments.create') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Buat opname</a>
        @endcan
    </div>

    <x-ui.table :headers="['Tanggal', 'Jenis', 'Produk', 'Stok sistem', 'Stok setelah', 'Selisih', 'Alasan', 'Pengguna']" :paginator="$adjustments">
        @forelse ($adjustments as $adjustment)
            <tr>
                <td class="whitespace-nowrap px-5 py-4">{{ $adjustment->created_at->format('d/m/Y H:i') }}</td>
                <td class="whitespace-nowrap px-5 py-4">
                    {{ match ($adjustment->stockMovement?->tipe) {
                        'masuk' => 'Tambah manual',
                        'keluar' => 'Kurangi manual',
                        default => 'Stok opname',
                    } }}
                </td>
                <td class="whitespace-nowrap px-5 py-4">
                    <a href="{{ route('stock.show', $adjustment->product) }}" class="font-medium text-indigo-700 hover:underline">{{ $adjustment->product->nama }}</a>
                    <p class="mt-1 text-xs text-slate-500">{{ $adjustment->product->sku }}</p>
                </td>
                <td class="whitespace-nowrap px-5 py-4 text-right">{{ number_format($adjustment->qty_sistem, 0, ',', '.') }}</td>
                <td class="whitespace-nowrap px-5 py-4 text-right">{{ number_format($adjustment->qty_fisik, 0, ',', '.') }}</td>
                <td class="whitespace-nowrap px-5 py-4 text-right @class(['text-green-700' => $adjustment->selisih > 0, 'text-red-700' => $adjustment->selisih < 0])">
                    {{ $adjustment->selisih > 0 ? '+' : '' }}{{ number_format($adjustment->selisih, 0, ',', '.') }}
                </td>
                <td class="max-w-64 px-5 py-4">{{ $adjustment->alasan }}</td>
                <td class="whitespace-nowrap px-5 py-4">{{ $adjustment->user?->name ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="8" class="px-5 py-10 text-center text-slate-500">Belum ada riwayat perubahan stok.</td></tr>
        @endforelse
    </x-ui.table>
@endsection
