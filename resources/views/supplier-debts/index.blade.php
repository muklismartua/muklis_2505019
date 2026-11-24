@extends('layouts.app')

@section('title', 'Hutang Supplier')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Hutang Supplier</h1>
        <p class="mt-1 text-sm text-slate-600">Daftar tagihan pembelian yang belum dibayar atau sudah dibayar.</p>
    </div>

    <form method="GET" action="{{ route('supplier-debts.index') }}" class="mb-5 grid gap-3 rounded-lg border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-5">
        <x-ui.select name="supplier_id" label="Supplier" :options="$suppliers" :selected="request('supplier_id')" placeholder="Semua supplier" />
        <x-ui.select name="status" label="Status hutang" :options="['belum_lunas' => 'Belum lunas', 'lunas' => 'Lunas', 'batal' => 'Batal']" :selected="request('status')" placeholder="Semua status" />
        <x-ui.input name="jatuh_dari" label="Jatuh tempo dari" type="date" :value="request('jatuh_dari')" />
        <x-ui.input name="jatuh_sampai" label="Jatuh tempo sampai" type="date" :value="request('jatuh_sampai')" />
        <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>
    </form>

    <x-ui.table :headers="['Pembelian', 'Supplier', 'Jatuh tempo', 'Total', 'Terbayar', 'Sisa', 'Status']" :paginator="$hutangs">
        @forelse ($hutangs as $hutang)
            @php
                $late = $hutang->status === 'belum_lunas' && $hutang->jatuh_tempo?->lt(today());
                $soon = $hutang->status === 'belum_lunas'
                    && $hutang->jatuh_tempo?->gte(today())
                    && $hutang->jatuh_tempo?->lte(today()->addDays(3));
            @endphp
            <tr>
                <td class="whitespace-nowrap px-5 py-4 font-medium">
                    <a href="{{ route('supplier-debts.show', $hutang) }}" class="text-indigo-700 hover:underline">{{ $hutang->purchase->nomor }}</a>
                </td>
                <td class="whitespace-nowrap px-5 py-4">{{ $hutang->supplier->nama }}</td>
                <td class="whitespace-nowrap px-5 py-4">
                    {{ $hutang->jatuh_tempo?->format('d/m/Y') ?? '—' }}
                    @if ($late)
                        <span class="ml-1 rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-800">Jatuh tempo</span>
                    @elseif ($soon)
                        <span class="ml-1 rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800">Segera jatuh tempo</span>
                    @endif
                </td>
                <td class="whitespace-nowrap px-5 py-4 text-right">{{ rupiah($hutang->total) }}</td>
                <td class="whitespace-nowrap px-5 py-4 text-right">{{ rupiah($hutang->terbayar) }}</td>
                <td class="whitespace-nowrap px-5 py-4 text-right">{{ rupiah($hutang->sisa) }}</td>
                <td class="whitespace-nowrap px-5 py-4">
                    <x-ui.badge :status="$hutang->status === 'belum_lunas' ? 'warning' : ($hutang->status === 'lunas' ? 'success' : 'inactive')">{{ str_replace('_', ' ', ucfirst($hutang->status)) }}</x-ui.badge>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="px-5 py-10 text-center text-slate-500">Belum ada data hutang supplier.</td></tr>
        @endforelse
    </x-ui.table>
@endsection
