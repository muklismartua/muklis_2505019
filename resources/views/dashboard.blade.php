<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold text-slate-900">Dashboard</h1>
    </x-slot>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm" aria-label="Kartu ringkasan penjualan hari ini">
            <p class="text-sm font-medium text-slate-500">Total penjualan hari ini</p>
            <p class="mt-3 text-2xl font-bold text-slate-900">{{ $stats['sales_today'] === null ? '—' : rupiah($stats['sales_today']) }}</p>
            @if ($stats['sales_today'] !== null && auth()->user()->hasRole('kasir'))
                <p class="mt-2 text-xs text-slate-500">Transaksi milik Anda</p>
            @endif
        </section>
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm" aria-label="Kartu ringkasan penjualan bulan ini">
            <p class="text-sm font-medium text-slate-500">Total penjualan bulan ini</p>
            <p class="mt-3 text-2xl font-bold text-slate-900">{{ $stats['sales_this_month'] === null ? '—' : rupiah($stats['sales_this_month']) }}</p>
            @if ($stats['sales_this_month'] !== null && auth()->user()->hasRole('kasir'))
                <p class="mt-2 text-xs text-slate-500">Transaksi milik Anda</p>
            @endif
        </section>
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm" aria-label="Kartu kas masuk hari ini">
            <p class="text-sm font-medium text-slate-500">Total diterima hari ini</p>
            <p class="mt-3 text-2xl font-bold text-emerald-700">{{ $stats['cash_received_today'] === null ? '—' : rupiah($stats['cash_received_today']) }}</p>
            <p class="mt-2 text-xs text-slate-500">Kas masuk dari penjualan dan cicilan</p>
        </section>
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm" aria-label="Kartu kas masuk bulan ini">
            <p class="text-sm font-medium text-slate-500">Total diterima bulan ini</p>
            <p class="mt-3 text-2xl font-bold text-emerald-700">{{ $stats['cash_received_this_month'] === null ? '—' : rupiah($stats['cash_received_this_month']) }}</p>
            <p class="mt-2 text-xs text-slate-500">Kas masuk dari penjualan dan cicilan</p>
        </section>
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm" aria-label="Kartu ringkasan jumlah transaksi hari ini">
            <p class="text-sm font-medium text-slate-500">Jumlah transaksi hari ini</p>
            <p class="mt-3 text-2xl font-bold text-slate-900">{{ number_format($stats['transactions_today'], 0, ',', '.') }}</p>
            <p class="mt-2 text-xs text-slate-500">Transaksi selesai</p>
        </section>
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm" aria-label="Kartu ringkasan stok menipis">
            <p class="text-sm font-medium text-slate-500">Produk stok menipis</p>
            <p class="mt-3 text-2xl font-bold text-slate-900">{{ $stats['low_stock'] === null ? '—' : number_format($stats['low_stock'], 0, ',', '.') }}</p>
            @if ($stats['low_stock'] !== null && $can_open_stock)
                <a href="{{ route('stock.low-stock') }}" class="mt-2 inline-block text-xs font-medium text-indigo-700 hover:underline">Lihat stok menipis</a>
            @elseif ($stats['low_stock'] !== null)
                <p class="mt-2 text-xs text-slate-500">Ringkasan stok tersedia untuk Anda</p>
            @else
                <p class="mt-2 text-xs text-slate-500">Tidak tersedia untuk role Anda</p>
            @endif
        </section>
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm" aria-label="Kartu total piutang aktif">
            <p class="text-sm font-medium text-slate-500">Total Piutang Aktif</p>
            <p class="mt-3 text-2xl font-bold text-slate-900">{{ $stats['active_receivables'] === null ? '—' : rupiah($stats['active_receivables']) }}</p>
            @unless ($can_view_receivables)
                <p class="mt-2 text-xs text-slate-500">Tidak tersedia untuk role Anda</p>
            @endunless
        </section>
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm" aria-label="Kartu piutang jatuh tempo">
            <p class="text-sm font-medium text-slate-500">Piutang Jatuh Tempo</p>
            @if ($stats['overdue_receivable_count'] === null)
                <p class="mt-3 text-2xl font-bold text-slate-900">—</p>
                <p class="mt-2 text-xs text-slate-500">Tidak tersedia untuk role Anda</p>
            @else
                <p class="mt-3 text-2xl font-bold text-red-700">{{ number_format($stats['overdue_receivable_count'], 0, ',', '.') }} piutang</p>
                <p class="mt-2 text-sm font-semibold text-red-700">{{ rupiah($stats['overdue_receivable_total']) }}</p>
            @endif
        </section>
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm" aria-label="Kartu total hutang supplier aktif">
            <p class="text-sm font-medium text-slate-500">Total Hutang Supplier Aktif</p>
            <p class="mt-3 text-2xl font-bold text-slate-900">{{ $stats['active_supplier_debts'] === null ? '—' : rupiah($stats['active_supplier_debts']) }}</p>
            @unless ($can_view_supplier_debts)
                <p class="mt-2 text-xs text-slate-500">Tidak tersedia untuk role Anda</p>
            @endunless
        </section>
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm" aria-label="Kartu hutang supplier jatuh tempo">
            <p class="text-sm font-medium text-slate-500">Hutang Supplier Jatuh Tempo</p>
            @if ($stats['overdue_supplier_debt_count'] === null)
                <p class="mt-3 text-2xl font-bold text-slate-900">—</p>
                <p class="mt-2 text-xs text-slate-500">Tidak tersedia untuk role Anda</p>
            @else
                <p class="mt-3 text-2xl font-bold text-red-700">{{ number_format($stats['overdue_supplier_debt_count'], 0, ',', '.') }} hutang</p>
                <p class="mt-2 text-sm font-semibold text-red-700">{{ rupiah($stats['overdue_supplier_debt_total']) }}</p>
            @endif
        </section>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-2">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-semibold text-slate-900">Grafik penjualan</h2>
                    @if (auth()->user()->hasRole('kasir'))
                        <p class="mt-1 text-xs text-slate-500">Menampilkan transaksi milik Anda.</p>
                    @endif
                </div>
                <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2">
                    <label for="hari" class="text-sm text-slate-600">Periode</label>
                    <select id="hari" name="hari" onchange="this.form.submit()" class="rounded-md border-slate-300 py-1.5 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="7" @selected($days === 7)>7 hari</option>
                        <option value="30" @selected($days === 30)>30 hari</option>
                    </select>
                </form>
            </div>
            <div class="relative h-72">
                <canvas
                    data-sales-chart
                    data-labels='@js($trend["labels"])'
                    data-values='@js($trend["values"])'
                    aria-label="Grafik penjualan {{ $days }} hari terakhir"
                    role="img"
                ></canvas>
                @unless ($can_view_sales)
                    <div class="absolute inset-0 flex items-center justify-center bg-white/80 text-sm text-slate-500">
                        Data penjualan tidak tersedia untuk role Anda.
                    </div>
                @endunless
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-slate-900">5 produk terlaris</h2>
            <p class="mt-1 text-xs text-slate-500">Berdasarkan jumlah terjual 30 hari terakhir.</p>
            @if ($can_view_sales)
                <div class="mt-4 space-y-4">
                    @forelse ($top_products as $product)
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-slate-800">{{ $product->nama }}</p>
                                <p class="text-xs text-slate-500">{{ $product->sku }}</p>
                            </div>
                            <span class="whitespace-nowrap text-sm font-semibold text-slate-700">{{ number_format($product->qty_terjual, 0, ',', '.') }} terjual</span>
                        </div>
                    @empty
                        <p class="py-6 text-center text-sm text-slate-500">Belum ada penjualan pada periode ini.</p>
                    @endforelse
                </div>
            @else
                <p class="mt-4 py-6 text-center text-sm text-slate-500">Data tidak tersedia untuk role Anda.</p>
            @endif
        </section>
    </div>

    <section class="mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4">
            <h2 class="font-semibold text-slate-900">5 pelanggan dengan hutang terbesar</h2>
            @if (auth()->user()->hasRole('kasir'))
                <p class="mt-1 text-xs text-slate-500">Berdasarkan piutang dari penjualan milik Anda.</p>
            @endif
        </div>
        @if ($can_view_receivables)
            <x-ui.table :headers="['Pelanggan', 'Total piutang aktif']">
                @forelse ($top_debtors as $debtor)
                    <tr>
                        <td class="px-5 py-4 font-medium">{{ $debtor->nama }}</td>
                        <td class="whitespace-nowrap px-5 py-4 text-right font-semibold">{{ rupiah((int) $debtor->total_hutang) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="px-5 py-8 text-center text-slate-500">Belum ada piutang aktif.</td></tr>
                @endforelse
            </x-ui.table>
        @else
            <p class="py-6 text-center text-sm text-slate-500">Data tidak tersedia untuk role Anda.</p>
        @endif
    </section>

    <section class="mt-6">
        <div class="mb-3">
            <h2 class="font-semibold text-slate-900">5 transaksi terbaru</h2>
            @if (auth()->user()->hasRole('kasir'))
                <p class="mt-1 text-xs text-slate-500">Hanya menampilkan transaksi penjualan milik Anda.</p>
            @endif
        </div>
        <x-ui.table :headers="['Jenis', 'Nomor', 'Tanggal', 'Total', 'Status']">
            @forelse ($recent_transactions as $transaction)
                <tr>
                    <td class="whitespace-nowrap px-5 py-4">{{ $transaction['jenis'] }}</td>
                    <td class="whitespace-nowrap px-5 py-4 font-medium">
                        <a href="{{ $transaction['tujuan'] }}" class="text-indigo-700 hover:underline">{{ $transaction['nomor'] }}</a>
                    </td>
                    <td class="whitespace-nowrap px-5 py-4">{{ $transaction['tanggal']->format('d/m/Y') }}</td>
                    <td class="whitespace-nowrap px-5 py-4 text-right">{{ rupiah($transaction['total']) }}</td>
                    <td class="whitespace-nowrap px-5 py-4">
                        <x-ui.badge :status="$transaction['status'] === 'selesai' ? 'success' : 'inactive'">{{ ucfirst($transaction['status']) }}</x-ui.badge>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">Belum ada transaksi.</td></tr>
            @endforelse
        </x-ui.table>
    </section>
</x-app-layout>
