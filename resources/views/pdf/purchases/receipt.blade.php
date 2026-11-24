@extends('pdf.layout')

@section('document')
    <h2 class="document-title">BUKTI PEMBELIAN</h2>

    <table class="meta">
        <tr><td style="width: 105px">Nomor</td><td style="width: 10px">:</td><td>{{ $purchase->nomor }}</td></tr>
        <tr><td>Tanggal</td><td>:</td><td>{{ $purchase->tanggal->format('d/m/Y') }}</td></tr>
        <tr><td>Supplier</td><td>:</td><td>{{ $purchase->supplier->nama }}</td></tr>
        <tr><td>Metode pembayaran</td><td>:</td><td>{{ $purchase->metode_pembayaran === 'tempo' ? 'Hutang (Bayar Nanti)' : ucfirst($purchase->metode_pembayaran) }}</td></tr>
        <tr><td>Status</td><td>:</td><td>{{ ucfirst($purchase->status) }}</td></tr>
        <tr><td>Status pembayaran</td><td>:</td><td>{{ $purchase->status_pembayaran === 'lunas' ? 'LUNAS' : 'BELUM LUNAS' }}</td></tr>
        @if ($purchase->status_pembayaran === 'belum_lunas')
            <tr><td>DP</td><td>:</td><td>{{ rupiah($purchase->total_dibayar) }}</td></tr>
            <tr><td>Sisa hutang</td><td>:</td><td>{{ rupiah($purchase->sisa) }}</td></tr>
            <tr><td>Jatuh tempo</td><td>:</td><td>{{ $purchase->jatuh_tempo?->format('d/m/Y') ?? '—' }}</td></tr>
        @endif
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 40%">Produk</th>
                <th class="right">Qty</th>
                <th class="right">Harga beli</th>
                <th class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($purchase->items as $item)
                <tr>
                    <td>{{ $item->product?->nama ?? 'Produk dihapus' }}</td>
                    <td class="right">{{ number_format($item->qty, 0, ',', '.') }}</td>
                    <td class="right">{{ rupiah($item->harga_beli) }}</td>
                    <td class="right">{{ rupiah($item->subtotal) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="right">{{ rupiah($purchase->subtotal) }}</td></tr>
        <tr><td>Diskon</td><td class="right">{{ rupiah($purchase->diskon) }}</td></tr>
        <tr class="grand-total"><td>Total</td><td class="right">{{ rupiah($purchase->total) }}</td></tr>
    </table>

    @if ($purchase->catatan)
        <p class="notes"><strong>Catatan:</strong> {{ $purchase->catatan }}</p>
    @endif
@endsection
