@extends('pdf.layout')

@section('document')
    @php($totalDibayar = (int) ($sale->total_dibayar ?? 0) > 0 || (int) ($sale->sisa ?? 0) > 0 ? (int) $sale->total_dibayar : min((int) $sale->bayar, (int) $sale->total))
    <h2 class="document-title">INVOICE PENJUALAN</h2>

    <table class="meta">
        <tr><td style="width: 105px">Nomor</td><td style="width: 10px">:</td><td>{{ $sale->nomor }}</td></tr>
        <tr><td>Tanggal</td><td>:</td><td>{{ $sale->tanggal->format('d/m/Y') }}</td></tr>
        <tr><td>Pelanggan</td><td>:</td><td>{{ $sale->customer?->nama ?? 'Pelanggan umum' }}</td></tr>
        <tr><td>Metode pembayaran</td><td>:</td><td>{{ $sale->metode_pembayaran === 'tempo' ? 'Hutang (Bayar Nanti)' : ucfirst($sale->metode_pembayaran) }}</td></tr>
        <tr><td>Status</td><td>:</td><td>{{ ucfirst($sale->status) }}</td></tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 35%">Produk</th>
                <th class="right">Qty</th>
                <th class="right">Harga</th>
                <th class="right">Diskon</th>
                <th class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $item)
                <tr>
                    <td>{{ $item->product?->nama ?? 'Produk dihapus' }}</td>
                    <td class="right">{{ number_format($item->qty, 0, ',', '.') }}</td>
                    <td class="right">{{ rupiah($item->harga_jual) }}</td>
                    <td class="right">{{ rupiah($item->diskon) }}</td>
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
        @if ($sale->metode_pembayaran === 'tempo')
            <tr><td>DP / Uang Muka</td><td class="right">{{ rupiah($sale->bayar) }}</td></tr>
            <tr><td>Total diterima sampai saat ini</td><td class="right">{{ rupiah($totalDibayar) }}</td></tr>
            <tr><td>Sisa hutang</td><td class="right">{{ rupiah((int) ($sale->sisa ?? 0)) }}</td></tr>
            <tr><td>Jatuh tempo</td><td class="right">{{ $sale->jatuh_tempo?->format('d/m/Y') ?? '—' }}</td></tr>
            <tr><td>Status pembayaran</td><td class="right"><strong>{{ $sale->status === 'batal' ? 'BATAL' : (($sale->sisa ?? 0) > 0 ? 'BELUM LUNAS' : 'LUNAS') }}</strong></td></tr>
        @else
            <tr><td>Bayar</td><td class="right">{{ rupiah($sale->bayar) }}</td></tr>
            <tr><td>Total dibayar</td><td class="right">{{ rupiah($totalDibayar) }}</td></tr>
        @endif
        <tr><td>Kembalian</td><td class="right">{{ rupiah($sale->kembalian) }}</td></tr>
    </table>

    @if ($sale->catatan)
        <p class="notes"><strong>Catatan:</strong> {{ $sale->catatan }}</p>
    @endif
@endsection
