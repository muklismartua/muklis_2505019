@extends('layouts.transaction')

@section('title', 'Beranda')

@section('content')
    <section class="rounded-xl border border-slate-200 bg-white px-6 py-12 text-center shadow-sm sm:px-12">
        <p class="text-sm font-semibold uppercase tracking-wide text-indigo-700">Manajemen stok dan transaksi</p>
        <h1 class="mx-auto mt-3 max-w-2xl text-3xl font-bold tracking-tight sm:text-4xl">Kelola penjualan dan pembelian stok dalam satu tempat</h1>
        <p class="mx-auto mt-4 max-w-xl text-slate-600">Catat transaksi multi-item, pantau riwayat, dan pastikan perubahan stok tercatat.</p>
        @auth
            @canany(['penjualan.lihat', 'pembelian.lihat'])
                <a href="{{ route('transactions.index') }}" class="mt-8 inline-flex rounded-md bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-700">Buka transaksi</a>
            @else
                <a href="{{ route('dashboard') }}" class="mt-8 inline-flex rounded-md bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-700">Buka dasbor</a>
            @endcanany
        @else
            <a href="{{ route('login') }}" class="mt-8 inline-flex rounded-md bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-700">Masuk</a>
        @endauth
    </section>
@endsection
