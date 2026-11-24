<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 14mm; }
        body { color: #1f2937; font-family: "DejaVu Sans", sans-serif; font-size: 9px; }
        h1 { margin: 0 0 5px; font-size: 16px; }
        p { margin: 0 0 14px; color: #4b5563; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #9ca3af; padding: 5px; }
        th { background: #f3f4f6; text-align: left; }
        .right { text-align: right; white-space: nowrap; }
    </style>
</head>
<body>
    <h1>{{ $definition['title'] }}</h1>
    <p>
        {{ $definition['description'] }}
        @if ($definition['filters']['periode'] ?? false)
            Periode {{ \Illuminate\Support\Carbon::parse($filters['dari'])->format('d/m/Y') }}
            sampai {{ \Illuminate\Support\Carbon::parse($filters['sampai'])->format('d/m/Y') }}.
        @endif
        @if ($definition['filters']['rentang_jatuh_tempo'] ?? false)
            Jatuh tempo:
            {{ $filters['jatuh_dari'] ? \Illuminate\Support\Carbon::parse($filters['jatuh_dari'])->format('d/m/Y') : 'awal' }}
            sampai
            {{ $filters['jatuh_sampai'] ? \Illuminate\Support\Carbon::parse($filters['jatuh_sampai'])->format('d/m/Y') : 'akhir' }}.
        @endif
    </p>
    @if ($stockValueTotal !== null)
        <p><strong>Total nilai persediaan:</strong> {{ rupiah($stockValueTotal) }}</p>
    @endif
    <table>
        <thead>
            <tr>
                @foreach ($definition['columns'] as $column)
                    <th>{{ $column['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($definition['columns'] as $column)
                        @php($value = $row[$column['key']] ?? null)
                        <td @class(['right' => in_array($column['type'], ['money', 'number'], true)])>
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
                <tr><td colspan="{{ count($definition['columns']) }}">Tidak ada data untuk filter yang dipilih.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
