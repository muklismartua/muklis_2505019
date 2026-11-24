@extends('pdf.layout')

@section('document')
    <h2 class="document-title">BUKTI PEMBAYARAN CICILAN PIUTANG</h2>

    <table class="meta">
        <tr><td style="width: 130px">Nomor pembayaran</td><td style="width: 10px">:</td><td>{{ $payment->nomor }}</td></tr>
        <tr><td>Tanggal pembayaran</td><td>:</td><td>{{ $payment->tanggal->format('d/m/Y') }}</td></tr>
        <tr><td>Nomor penjualan</td><td>:</td><td>{{ $piutang->sale?->nomor ?? '—' }}</td></tr>
        <tr><td>Pelanggan</td><td>:</td><td>{{ $piutang->customer?->nama ?? 'Pelanggan dihapus' }}</td></tr>
        <tr><td>Metode pembayaran</td><td>:</td><td>{{ ucfirst($payment->metode) }}</td></tr>
        <tr><td>Diterima oleh</td><td>:</td><td>{{ $payment->user?->name ?? '—' }}</td></tr>
    </table>

    <table class="totals">
        <tr class="grand-total"><td>Jumlah diterima</td><td class="right">{{ rupiah($payment->jumlah) }}</td></tr>
        <tr><td>Sisa piutang setelah pembayaran</td><td class="right">{{ rupiah($sisaSetelahPembayaran) }}</td></tr>
    </table>

    @if ($payment->catatan)
        <p class="notes"><strong>Catatan:</strong> {{ $payment->catatan }}</p>
    @endif
@endsection
