@extends('layouts.app')

@section('title', 'Detail Pelanggan')

@section('content')
    <div class="mb-6">
        <a href="{{ route('customers.index') }}" class="text-sm font-medium text-indigo-700 hover:underline">&larr; Kembali ke pelanggan</a>
        <div class="mt-3 flex items-start justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-slate-500">{{ $customer->kode }}</p>
                <h1 class="mt-1 text-2xl font-bold">{{ $customer->nama }}</h1>
            </div>
            @can('pelanggan.kelola')
                <a href="{{ route('customers.edit', $customer) }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Edit</a>
            @endcan
        </div>
    </div>

    <dl class="max-w-3xl divide-y divide-slate-100 rounded-lg border border-slate-200 bg-white px-5">
        <div class="grid gap-1 py-4 sm:grid-cols-3"><dt class="text-sm font-medium text-slate-500">Telepon</dt><dd class="text-sm text-slate-900 sm:col-span-2">{{ $customer->telepon ?: '—' }}</dd></div>
        <div class="grid gap-1 py-4 sm:grid-cols-3"><dt class="text-sm font-medium text-slate-500">Email</dt><dd class="text-sm text-slate-900 sm:col-span-2">{{ $customer->email ?: '—' }}</dd></div>
        <div class="grid gap-1 py-4 sm:grid-cols-3"><dt class="text-sm font-medium text-slate-500">Alamat</dt><dd class="whitespace-pre-line text-sm text-slate-900 sm:col-span-2">{{ $customer->alamat ?: '—' }}</dd></div>
        <div class="grid gap-1 py-4 sm:grid-cols-3"><dt class="text-sm font-medium text-slate-500">Batas hutang</dt><dd class="text-sm text-slate-900 sm:col-span-2">{{ $customer->batas_hutang === 0 ? 'Tanpa batas' : rupiah($customer->batas_hutang) }}</dd></div>
        <div class="grid gap-1 py-4 sm:grid-cols-3"><dt class="text-sm font-medium text-slate-500">Status</dt><dd class="text-sm text-slate-900 sm:col-span-2">{{ $customer->aktif ? 'Aktif' : 'Nonaktif' }}</dd></div>
    </dl>

    @if ($canViewPiutang)
        <section class="mt-6">
            <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-xl font-bold">Piutang pelanggan</h2>
                    <p class="mt-1 text-sm text-slate-600">Total hutang aktif: <strong>{{ rupiah($totalHutang) }}</strong></p>
                </div>
            </div>
            <x-ui.table :headers="['Penjualan', 'Tanggal jatuh tempo', 'Total', 'Terbayar', 'Sisa', 'Status']" :paginator="$piutangs">
                @forelse ($piutangs as $piutang)
                    <tr>
                        <td class="whitespace-nowrap px-5 py-4">
                            <a href="{{ route('piutangs.show', $piutang) }}" class="font-medium text-indigo-700 hover:underline">{{ $piutang->sale?->nomor ?? 'Penjualan dihapus' }}</a>
                        </td>
                        <td class="whitespace-nowrap px-5 py-4">{{ $piutang->jatuh_tempo?->format('d/m/Y') ?? '—' }}</td>
                        <td class="whitespace-nowrap px-5 py-4 text-right">{{ rupiah($piutang->total) }}</td>
                        <td class="whitespace-nowrap px-5 py-4 text-right">{{ rupiah($piutang->terbayar) }}</td>
                        <td class="whitespace-nowrap px-5 py-4 text-right font-semibold">{{ rupiah($piutang->sisa) }}</td>
                        <td class="whitespace-nowrap px-5 py-4">{{ str_replace('_', ' ', ucfirst($piutang->status)) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">Pelanggan ini belum memiliki piutang.</td></tr>
                @endforelse
            </x-ui.table>
        </section>
    @endif
@endsection
