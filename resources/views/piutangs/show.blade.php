@extends('layouts.app')

@section('title', 'Detail Piutang')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('piutangs.index') }}" class="text-sm font-medium text-indigo-700 hover:underline">&larr; Kembali ke piutang</a>
            <h1 class="mt-3 text-2xl font-bold">Piutang {{ $piutang->sale?->nomor ?? '—' }}</h1>
            <p class="mt-1 text-sm text-slate-600">{{ $piutang->customer?->nama ?? 'Pelanggan dihapus' }}</p>
        </div>
        <x-ui.badge :status="$piutang->status === 'lunas' ? 'success' : ($piutang->status === 'batal' ? 'inactive' : 'warning')">
            {{ str_replace('_', ' ', ucfirst($piutang->status)) }}
        </x-ui.badge>
    </div>

    <div class="grid gap-5 lg:grid-cols-2">
        <section class="rounded-lg border border-slate-200 bg-white p-5">
            <h2 class="font-semibold">Ringkasan piutang</h2>
            <dl class="mt-4 grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-slate-500">Total tagihan</dt><dd class="mt-1 font-semibold">{{ rupiah($piutang->total) }}</dd></div>
                <div><dt class="text-slate-500">Terbayar</dt><dd class="mt-1 font-semibold">{{ rupiah($piutang->terbayar) }}</dd></div>
                <div><dt class="text-slate-500">Sisa tagihan</dt><dd class="mt-1 text-lg font-bold text-amber-700">{{ rupiah($piutang->sisa) }}</dd></div>
                <div>
                    <dt class="text-slate-500">Jatuh tempo</dt>
                    <dd class="mt-1 font-semibold">{{ $piutang->jatuh_tempo?->format('d/m/Y') ?? 'Tidak ditentukan' }}</dd>
                    @if ($piutang->status === \App\Models\Piutang::STATUS_UNPAID && $piutang->jatuh_tempo?->lt(today()))
                        <span class="mt-1 inline-flex rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-800">Jatuh tempo</span>
                    @elseif ($piutang->status === \App\Models\Piutang::STATUS_UNPAID && $piutang->jatuh_tempo?->lte(today()->addDays(3)))
                        <span class="mt-1 inline-flex rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800">Segera jatuh tempo</span>
                    @endif
                </div>
            </dl>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-5">
            <h2 class="font-semibold">Penjualan terkait</h2>
            @if ($piutang->sale)
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Nomor</dt><dd><a href="{{ route('sales.show', $piutang->sale) }}" class="font-medium text-indigo-700 hover:underline">{{ $piutang->sale->nomor }}</a></dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Tanggal</dt><dd>{{ $piutang->sale->tanggal->format('d/m/Y') }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Metode</dt><dd>{{ ucfirst($piutang->sale->metode_pembayaran) }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Status transaksi</dt><dd>{{ ucfirst($piutang->sale->status) }}</dd></div>
                </dl>
                <ul class="mt-4 divide-y divide-slate-100 border-t border-slate-100 text-sm">
                    @foreach ($piutang->sale->items as $item)
                        <li class="flex justify-between gap-3 py-2">
                            <span>{{ $item->product?->nama ?? 'Produk dihapus' }} × {{ number_format($item->qty, 0, ',', '.') }}</span>
                            <span>{{ rupiah($item->subtotal) }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="mt-3 text-sm text-slate-500">Data penjualan tidak tersedia.</p>
            @endif
        </section>
    </div>

    @can('piutang.kelola')
        @if ($piutang->status === \App\Models\Piutang::STATUS_UNPAID && $piutang->sisa > 0)
            <section class="mt-5 rounded-lg border border-indigo-200 bg-white p-5">
                <h2 class="font-semibold">Catat pembayaran / cicilan</h2>
                <form method="POST" action="{{ route('piutangs.payments.store', $piutang) }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                    @csrf
                    <x-ui.input name="jumlah" label="Jumlah pembayaran (rupiah)" type="number" min="1" :max="$piutang->sisa" step="1" :value="old('jumlah')" required />
                    <x-ui.select name="metode" label="Metode pembayaran" :options="['tunai' => 'Tunai', 'transfer' => 'Transfer']" :selected="old('metode')" required />
                    <div class="sm:col-span-2">
                        <label for="catatan" class="mb-1 block text-sm font-medium text-slate-700">Catatan (opsional)</label>
                        <textarea id="catatan" name="catatan" rows="2" maxlength="2000" class="w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('catatan') }}</textarea>
                        @error('catatan')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex justify-end sm:col-span-2">
                        <x-ui.button type="submit">Simpan pembayaran</x-ui.button>
                    </div>
                </form>
            </section>
        @endif
    @endcan

    <section class="mt-5 overflow-hidden rounded-lg border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="font-semibold">Riwayat pembayaran</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Nomor</th>
                        <th class="px-5 py-3">Tanggal</th>
                        <th class="px-5 py-3 text-right">Jumlah</th>
                        <th class="px-5 py-3">Metode</th>
                        <th class="px-5 py-3">Dicatat oleh</th>
                        <th class="px-5 py-3">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($piutang->payments as $payment)
                        <tr>
                            <td class="whitespace-nowrap px-5 py-3 font-medium">{{ $payment->nomor }}</td>
                            <td class="whitespace-nowrap px-5 py-3">{{ $payment->tanggal->format('d/m/Y') }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-right">{{ rupiah($payment->jumlah) }}</td>
                            <td class="whitespace-nowrap px-5 py-3">{{ ucfirst($payment->metode) }}</td>
                            <td class="whitespace-nowrap px-5 py-3">{{ $payment->user?->name ?? '—' }}</td>
                            <td class="px-5 py-3">{{ $payment->catatan ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">Belum ada pembayaran cicilan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
