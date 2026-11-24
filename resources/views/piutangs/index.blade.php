@extends('layouts.app')

@section('title', 'Piutang Pelanggan')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Piutang Pelanggan</h1>
        <p class="mt-1 text-sm text-slate-600">Pantau sisa tagihan dan jatuh tempo pembayaran pelanggan.</p>
    </div>

    <form method="GET" action="{{ route('piutangs.index') }}" class="mb-5 grid gap-3 rounded-lg border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.select name="customer_id" label="Pelanggan" :options="$customers" :selected="$filters['customer_id'] ?? null" placeholder="Semua pelanggan" />
        <x-ui.select
            name="status"
            label="Status piutang"
            :options="['belum_lunas' => 'Belum lunas', 'lunas' => 'Lunas', 'batal' => 'Batal']"
            :selected="$filters['status'] ?? null"
            placeholder="Semua status"
        />
        <x-ui.input name="jatuh_tempo" label="Tanggal jatuh tempo" type="date" :value="$filters['jatuh_tempo'] ?? null" />
        <div class="flex items-end"><x-ui.button type="submit" variant="secondary" class="w-full">Filter</x-ui.button></div>
    </form>

    <x-ui.table :headers="['Penjualan', 'Pelanggan', 'Total', 'Terbayar', 'Sisa', 'Jatuh tempo', 'Status']" :paginator="$piutangs">
        @forelse ($piutangs as $piutang)
            <tr>
                <td class="whitespace-nowrap px-5 py-4 font-medium">
                    <a href="{{ route('piutangs.show', $piutang) }}" class="text-indigo-700 hover:underline">{{ $piutang->sale?->nomor ?? 'Penjualan dihapus' }}</a>
                </td>
                <td class="whitespace-nowrap px-5 py-4">{{ $piutang->customer?->nama ?? 'Pelanggan dihapus' }}</td>
                <td class="whitespace-nowrap px-5 py-4 text-right">{{ rupiah($piutang->total) }}</td>
                <td class="whitespace-nowrap px-5 py-4 text-right">{{ rupiah($piutang->terbayar) }}</td>
                <td class="whitespace-nowrap px-5 py-4 text-right font-semibold">{{ rupiah($piutang->sisa) }}</td>
                <td class="whitespace-nowrap px-5 py-4">
                    @if ($piutang->jatuh_tempo)
                        <span>{{ $piutang->jatuh_tempo->format('d/m/Y') }}</span>
                        @if ($piutang->status === \App\Models\Piutang::STATUS_UNPAID && $piutang->jatuh_tempo->lt(today()))
                            <span class="ml-1 inline-flex rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-800">Jatuh tempo</span>
                        @elseif ($piutang->status === \App\Models\Piutang::STATUS_UNPAID && $piutang->jatuh_tempo->lte(today()->addDays(3)))
                            <span class="ml-1 inline-flex rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800">Segera jatuh tempo</span>
                        @endif
                    @else
                        —
                    @endif
                </td>
                <td class="whitespace-nowrap px-5 py-4">
                    <x-ui.badge :status="$piutang->status === 'lunas' ? 'success' : ($piutang->status === 'batal' ? 'inactive' : 'warning')">
                        {{ str_replace('_', ' ', ucfirst($piutang->status)) }}
                    </x-ui.badge>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="px-5 py-10 text-center text-slate-500">Belum ada data piutang.</td></tr>
        @endforelse
    </x-ui.table>
@endsection
