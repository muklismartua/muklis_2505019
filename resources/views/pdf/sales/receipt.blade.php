@extends('pdf.layout')

@section('page_styles')
    @page {
        margin: 3mm;
    }
@endsection

@section('body_class', 'receipt')

@section('document')
    @php($totalDibayar = $sale->total_dibayar ?? min($sale->bayar, $sale->total))
    <h2 class="document-title">NOTA PENJUALAN</h2>

    <table class="meta">
        <tr><td style="width: 74px">Nomor</td><td style="width: 8px">:</td><td>{{ $sale->nomor }}</td></tr>
        <tr><td>Tanggal</td><td>:</td><td>{{ $sale->tanggal->format('d/m/Y') }}</td></tr>
        <tr><td>Pelanggan</td><td>:</td><td>{{ $sale->customer?->nama ?? 'Pelanggan umum' }}</td></tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Produk</th>
                <th class="right">Qty</th>
                <th class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $item)
                <tr>
                    <td>
                        {{ $item->product?->nama ?? 'Produk dihapus' }}<br>
                        {{ number_format($item->qty, 0, ',', '.') }} × {{ rupiah($item->harga_jual) }}
                        @if ($item->diskon > 0)
                            <br>Diskon item: {{ rupiah($item->diskon) }}
                        @endif
                    </td>
                    <td class="right">{{ number_format($item->qty, 0, ',', '.') }}</td>
                    <td class="right">{{ rupiah($item->subtotal) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="right">{{ rupiah($sale->subtotal) }}</td></tr>
        <tr><td>Diskon</td><td class="right">{{ rupiah($sale->diskon) }}</td></tr>
        <tr><td>Pajak</td><td class="right">{{ rupiah($sale->pajak) }}</td></tr>
        <tr class="grand-total"><td>Total</td><td class="right">{{ rupiah($sale->total) }}</td></tr>
        <tr><td>Bayar</td><td class="right">{{ rupiah($sale->bayar) }}</td></tr>
        <tr><td>Total dibayar</td><td class="right">{{ rupiah($totalDibayar) }}</td></tr>
        @if ($sale->piutang && $sale->sisa > 0)
            <tr><td>Sisa piutang</td><td class="right">{{ rupiah($sale->sisa) }}</td></tr>
        @endif
        <tr><td>Kembalian</td><td class="right">{{ rupiah($sale->kembalian) }}</td></tr>
    </table>

    <p class="notes">Metode: {{ ucfirst($sale->metode_pembayaran) }}</p>
    @if ($sale->catatan)
        <p class="notes"><strong>Catatan:</strong> {{ $sale->catatan }}</p>
    @endif
@endsection
