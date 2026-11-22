<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Halaman Tidak Ditemukan - {{ config('app.name', 'Tokoku') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-50 p-6 text-slate-900">
    <main class="max-w-lg rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm">
        <p class="text-sm font-bold uppercase tracking-widest text-indigo-600">Kesalahan 404</p>
        <h1 class="mt-3 text-2xl font-bold">Halaman tidak ditemukan</h1>
        <p class="mt-3 text-slate-600">Halaman yang Anda cari mungkin sudah dipindahkan atau alamatnya tidak tepat.</p>
        <a href="{{ auth()->check() ? route('dashboard') : route('home') }}" class="mt-6 inline-flex rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
            {{ auth()->check() ? 'Kembali ke dashboard' : 'Kembali ke halaman utama' }}
        </a>
    </main>
</body>
</html>
