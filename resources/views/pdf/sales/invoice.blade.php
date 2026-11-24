@extends('pdf.layout')

@section('document')
    <h2 class="document-title">INVOICE PENJUALAN</h2>

    <table class="meta">
        <tr><td style="width: 105px">Nomor</td><td style="width: 10px">:</td><td>{{ $sale->nomor }}</td></tr>
        <tr><td>Tanggal</td><td>:</td><td>{{ $sale->tanggal->format('d/m/Y') }}</td></tr>
        <tr><td>Pelanggan</td><td>:</td><td>{{ $sale->customer?->nama ?? 'Pelanggan umum' }}</td></tr>
        <tr><td>Metode pembayaran</td><td>:</td><td>{{ ucfirst($sale->metode_pembayaran) }}</td></tr>
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
        <tr><td>Bayar</td><td class="right">{{ rupiah($sale->bayar) }}</td></tr>
        @if ($sale->receivable)
            <tr><td>Sisa piutang</td><td class="right">{{ rupiah($sale->receivable->sisa) }}</td></tr>
        @endif
        <tr><td>Kembalian</td><td class="right">{{ rupiah($sale->kembalian) }}</td></tr>
    </table>

    @if ($sale->catatan)
        <p class="notes"><strong>Catatan:</strong> {{ $sale->catatan }}</p>
    @endif
@endsection
