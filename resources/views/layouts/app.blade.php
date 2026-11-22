<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Tokoku'))</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-slate-900">
    <div
        x-data="{ sidebarOpen: false, masterOpen: true, stockOpen: true, transactionsOpen: true }"
        @keydown.escape.window="sidebarOpen = false"
        class="min-h-screen bg-slate-50"
    >
        @auth
            <div
                x-cloak
                x-show="sidebarOpen"
                x-transition.opacity
                class="fixed inset-0 z-40 bg-slate-950/50 lg:hidden"
                aria-hidden="true"
                @click="sidebarOpen = false"
            ></div>

            <aside
                x-cloak
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
                class="fixed inset-y-0 left-0 z-50 flex w-72 transform flex-col border-r border-slate-200 bg-white transition-transform duration-200 ease-in-out lg:translate-x-0"
                aria-label="Navigasi utama"
            >
                <div class="flex h-16 items-center justify-between border-b border-slate-200 px-5">
                    <a href="{{ route('dashboard') }}" class="text-lg font-bold tracking-tight text-indigo-700">Tokoku</a>
                    <button
                        type="button"
                        class="rounded-md p-2 text-slate-500 hover:bg-slate-100 lg:hidden"
                        aria-label="Tutup menu navigasi"
                        @click="sidebarOpen = false"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path d="m6 6 12 12M18 6 6 18" stroke-linecap="round" stroke-width="2" />
                        </svg>
                    </button>
                </div>

                <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4 text-sm">
                    @can('dashboard.lihat')
                        <a
                            href="{{ route('dashboard') }}"
                            @click="sidebarOpen = false"
                            @class([
                                'flex items-center rounded-md px-3 py-2 font-medium',
                                'bg-indigo-50 text-indigo-700' => request()->routeIs('dashboard'),
                                'text-slate-700 hover:bg-slate-100' => ! request()->routeIs('dashboard'),
                            ])
                        >Dashboard</a>
                    @endcan

                    @canany(['kategori.lihat', 'kategori.kelola', 'satuan.lihat', 'satuan.kelola', 'produk.lihat', 'produk.kelola', 'pemasok.lihat', 'pemasok.kelola', 'pelanggan.lihat', 'pelanggan.kelola'])
                        <div class="pt-4">
                            <button type="button" class="flex w-full items-center justify-between px-3 py-2 text-left font-semibold text-slate-500 hover:text-slate-800" @click="masterOpen = !masterOpen">
                                <span>Master Data</span>
                                <svg class="h-4 w-4 transition-transform" :class="masterOpen ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.22 7.47a.75.75 0 0 1 1.06 0L10 11.19l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div x-show="masterOpen" x-transition class="space-y-1 pl-3">
                                @canany(['kategori.lihat', 'kategori.kelola'])
                                    <span class="block cursor-not-allowed rounded-md px-3 py-2 text-slate-400" aria-disabled="true" title="Modul belum tersedia">Kategori</span>
                                @endcanany
                                @canany(['satuan.lihat', 'satuan.kelola'])
                                    <span class="block cursor-not-allowed rounded-md px-3 py-2 text-slate-400" aria-disabled="true" title="Modul belum tersedia">Satuan</span>
                                @endcanany
                                @canany(['produk.lihat', 'produk.kelola'])
                                    <span class="block cursor-not-allowed rounded-md px-3 py-2 text-slate-400" aria-disabled="true" title="Modul belum tersedia">Produk</span>
                                @endcanany
                                @canany(['pemasok.lihat', 'pemasok.kelola'])
                                    <span class="block cursor-not-allowed rounded-md px-3 py-2 text-slate-400" aria-disabled="true" title="Modul belum tersedia">Supplier</span>
                                @endcanany
                                @canany(['pelanggan.lihat', 'pelanggan.kelola'])
                                    <span class="block cursor-not-allowed rounded-md px-3 py-2 text-slate-400" aria-disabled="true" title="Modul belum tersedia">Pelanggan</span>
                                @endcanany
                            </div>
                        </div>
                    @endcanany

                    @canany(['stok.lihat', 'stok.kelola'])
                        <div class="pt-2">
                            <button type="button" class="flex w-full items-center justify-between px-3 py-2 text-left font-semibold text-slate-500 hover:text-slate-800" @click="stockOpen = !stockOpen">
                                <span>Stok</span>
                                <svg class="h-4 w-4 transition-transform" :class="stockOpen ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.22 7.47a.75.75 0 0 1 1.06 0L10 11.19l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div x-show="stockOpen" x-transition class="space-y-1 pl-3">
                                @canany(['stok.lihat', 'stok.kelola'])
                                    <span class="block cursor-not-allowed rounded-md px-3 py-2 text-slate-400" aria-disabled="true" title="Modul belum tersedia">Kartu Stok</span>
                                @endcanany
                                @can('stok.kelola')
                                    <span class="block cursor-not-allowed rounded-md px-3 py-2 text-slate-400" aria-disabled="true" title="Modul belum tersedia">Stok Opname</span>
                                @endcan
                            </div>
                        </div>
                    @endcanany

                    @canany(['pembelian.lihat', 'pembelian.kelola', 'penjualan.lihat', 'penjualan.kelola'])
                        <div class="pt-2">
                            <button type="button" class="flex w-full items-center justify-between px-3 py-2 text-left font-semibold text-slate-500 hover:text-slate-800" @click="transactionsOpen = !transactionsOpen">
                                <span>Transaksi</span>
                                <svg class="h-4 w-4 transition-transform" :class="transactionsOpen ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.22 7.47a.75.75 0 0 1 1.06 0L10 11.19l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div x-show="transactionsOpen" x-transition class="space-y-1 pl-3">
                                @canany(['pembelian.lihat', 'pembelian.kelola'])
                                    <a href="{{ route('transactions.index', ['type' => 'pembelian']) }}" @click="sidebarOpen = false" class="block rounded-md px-3 py-2 text-slate-700 hover:bg-slate-100">Pembelian</a>
                                @endcanany
                                @canany(['penjualan.lihat', 'penjualan.kelola'])
                                    <a href="{{ route('transactions.index', ['type' => 'penjualan']) }}" @click="sidebarOpen = false" class="block rounded-md px-3 py-2 text-slate-700 hover:bg-slate-100">Penjualan</a>
                                @endcanany
                            </div>
                        </div>
                    @endcanany

                    @can('laporan.lihat')
                        <div class="pt-2">
                            <span class="block cursor-not-allowed rounded-md px-3 py-2 font-medium text-slate-400" aria-disabled="true" title="Modul belum tersedia">Laporan</span>
                        </div>
                    @endcan

                    @can('user.kelola')
                        <a href="{{ route('users.index') }}" @click="sidebarOpen = false" @class([
                            'mt-2 block rounded-md px-3 py-2 font-medium',
                            'bg-indigo-50 text-indigo-700' => request()->routeIs('users.*'),
                            'text-slate-700 hover:bg-slate-100' => ! request()->routeIs('users.*'),
                        ])>Pengguna</a>
                    @endcan

                    @can('pengaturan.kelola')
                        <span class="mt-2 block cursor-not-allowed rounded-md px-3 py-2 font-medium text-slate-400" aria-disabled="true" title="Modul belum tersedia">Pengaturan</span>
                    @endcan
                </nav>
            </aside>
        @endauth

        <div class="{{ auth()->check() ? 'lg:pl-72' : '' }}">
            <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3">
                    @auth
                        <button
                            type="button"
                            class="rounded-md p-2 text-slate-600 hover:bg-slate-100 lg:hidden"
                            :aria-expanded="sidebarOpen.toString()"
                            :aria-label="sidebarOpen ? 'Tutup menu navigasi' : 'Buka menu navigasi'"
                            @click="sidebarOpen = !sidebarOpen"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round" stroke-width="2" />
                            </svg>
                        </button>
                    @endauth
                    <a href="{{ auth()->check() ? route('dashboard') : route('home') }}" class="text-lg font-bold text-indigo-700 lg:hidden">Tokoku</a>
                    @isset($header)
                        <div class="hidden text-sm font-semibold text-slate-700 sm:block">{{ $header }}</div>
                    @endisset
                </div>

                <div class="ml-auto flex items-center gap-4">
                    @auth
                        <span class="hidden text-sm font-medium text-slate-700 sm:inline">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="rounded-md px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900">Keluar</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="rounded-md px-3 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-50">Masuk</a>
                    @endauth
                </div>
            </header>

            <main class="mx-auto max-w-7xl p-4 sm:p-6 lg:p-8">
                @if (session('success'))
                    <x-ui.alert type="success" :message="session('success')" class="mb-5" />
                @endif
                @if (session('error'))
                    <x-ui.alert type="error" :message="session('error')" class="mb-5" />
                @endif
                @if ($errors->any())
                    <x-ui.alert type="error" :message="$errors->first()" class="mb-5" />
                @endif

                @isset($header)
                    <div class="mb-6 text-xl font-semibold text-slate-900 sm:hidden">{{ $header }}</div>
                @endisset

                @if (isset($slot))
                    {{ $slot }}
                @else
                    @yield('content')
                @endif
            </main>
        </div>
    </div>
</body>
</html>
