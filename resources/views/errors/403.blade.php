<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Akses Ditolak - {{ config('app.name', 'Tokoku') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-50 p-6 text-slate-900">
    <main class="max-w-lg rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm">
        <p class="text-sm font-bold uppercase tracking-widest text-indigo-600">Kesalahan 403</p>
        <h1 class="mt-3 text-2xl font-bold">Akses ditolak</h1>
        <p class="mt-3 text-slate-600">Anda tidak memiliki izin untuk membuka halaman ini. Hubungi administrator jika Anda merasa ini sebuah kesalahan.</p>
        <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="mt-6 inline-flex rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
            {{ auth()->check() ? 'Kembali ke dashboard' : 'Masuk' }}
        </a>
    </main>
</body>
</html>
