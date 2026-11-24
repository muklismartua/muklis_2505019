@extends('layouts.app')

@section('title', $definition['title'])

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold">{{ $definition['title'] }}</h1>
        <p class="mt-1 text-sm text-slate-600">{{ $definition['description'] }}</p>
    </div>

    <div class="mb-5 flex flex-wrap gap-2">
        @foreach ($reports as $key => $option)
            <a
                href="{{ route('reports.show', ['report' => $key]) }}"
                @class([
                    'rounded-md px-3 py-2 text-sm font-medium',
                    'bg-indigo-600 text-white' => $key === $report,
                    'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' => $key !== $report,
                ])
            >{{ $option['title'] }}</a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('reports.show', ['report' => $report]) }}" class="mb-5 grid gap-3 rounded-lg border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-6">
        @if ($definition['filters']['periode'] ?? false)
            <x-ui.input name="dari" label="Dari tanggal" type="date" :value="$filters['dari']" />
            <x-ui.input name="sampai" label="Sampai tanggal" type="date" :value="$filters['sampai']" />
        @endif
        @if ($definition['filters']['produk'] ?? false)
            <x-ui.select name="product_id" label="Produk" :options="$products" :selected="$filters['product_id'] ?? null" placeholder="Semua produk" />
        @endif
        @if ($definition['filters']['pelanggan'] ?? false)
            <x-ui.select name="customer_id" label="Pelanggan" :options="$customers" :selected="$filters['customer_id'] ?? null" placeholder="Semua pelanggan" />
        @endif
        @if ($definition['filters']['supplier'] ?? false)
            <x-ui.select name="supplier_id" label="Supplier" :options="$suppliers" :selected="$filters['supplier_id'] ?? null" placeholder="Semua supplier" />
        @endif
        @if ($definition['filters']['status'] ?? false)
            <x-ui.select name="status" label="Status piutang" :options="$statuses" :selected="$filters['status'] ?? null" placeholder="Semua status" />
        @endif
        @if ($definition['filters']['rentang_jatuh_tempo'] ?? false)
            <x-ui.input name="jatuh_dari" label="Jatuh tempo dari" type="date" :value="$filters['jatuh_dari']" />
            <x-ui.input name="jatuh_sampai" label="Jatuh tempo sampai" type="date" :value="$filters['jatuh_sampai']" />
        @endif
        <div class="flex items-end gap-2">
            <x-ui.button type="submit" variant="secondary" class="w-full">Terapkan</x-ui.button>
        </div>
    </form>

    <div class="mb-4 flex flex-wrap justify-end gap-2">
        <a href="{{ route('reports.excel', ['report' => $report] + request()->query()) }}" class="rounded-md border border-emerald-300 bg-white px-4 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-50">Ekspor Excel</a>
        <a href="{{ route('reports.pdf', ['report' => $report] + request()->query()) }}" class="rounded-md border border-red-300 bg-white px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Unduh PDF</a>
    </div>

    @if ($report === 'stok-saat-ini')
        <div class="mb-4 rounded-lg border border-indigo-100 bg-indigo-50 p-4">
            <p class="text-sm font-medium text-indigo-900">Total nilai persediaan</p>
            <p class="mt-1 text-xl font-bold text-indigo-950">{{ rupiah($stockValueTotal) }}</p>
        </div>
    @endif

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        @foreach ($definition['columns'] as $column)
                            <th scope="col" class="whitespace-nowrap px-5 py-3">{{ $column['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rows as $row)
                        <tr>
                            @foreach ($definition['columns'] as $column)
                                @php($value = $row[$column['key']] ?? null)
                                <td @class(['whitespace-nowrap px-5 py-4', 'text-right' => in_array($column['type'], ['money', 'number'], true)])>
                                    @if ($column['type'] === 'money')
                                        {{ rupiah((int) $value) }}
                                    @elseif ($column['type'] === 'number')
                                        {{ number_format((int) $value, 0, ',', '.') }}
                                    @elseif ($value === null)
                                        —
                                    @elseif ($column['type'] === 'date')
                                        {{ \Illuminate\Support\Carbon::parse($value)->format('d/m/Y') }}
                                    @elseif ($column['type'] === 'datetime')
                                        {{ \Illuminate\Support\Carbon::parse($value)->format('d/m/Y H:i') }}
                                    @else
                                        {{ $value ?? '—' }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($definition['columns']) }}" class="px-5 py-10 text-center text-slate-500">Tidak ada data untuk filter yang dipilih.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($rows instanceof \Illuminate\Contracts\Pagination\Paginator && $rows->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">{{ $rows->links() }}</div>
        @endif
    </div>

    @if ($report === 'laba-rugi')
        <p class="mt-3 text-xs text-slate-500">{{ $definition['description'] }}</p>
    @endif
@endsection
