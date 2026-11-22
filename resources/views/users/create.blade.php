@extends('layouts.app')

@section('title', 'Tambah Pengguna')

@section('content')
    <div class="mb-6">
        <a href="{{ route('users.index') }}" class="text-sm font-medium text-indigo-700 hover:underline">&larr; Kembali ke pengguna</a>
        <h1 class="mt-3 text-2xl font-bold">Tambah pengguna</h1>
    </div>

    <form method="POST" action="{{ route('users.store') }}" class="max-w-2xl space-y-5 rounded-lg border border-slate-200 bg-white p-6">
        @csrf
        <x-ui.input name="name" label="Nama" :value="old('name')" required maxlength="255" />
        <x-ui.input name="email" label="Email" type="email" :value="old('email')" required maxlength="255" />
        <x-ui.select name="role" label="Role" :options="$roles" :selected="old('role')" placeholder="Pilih role" required />
        <x-ui.input name="password" label="Kata sandi" type="password" required autocomplete="new-password" />
        <x-ui.input name="password_confirmation" label="Konfirmasi kata sandi" type="password" required autocomplete="new-password" />
        <div class="flex justify-end gap-3">
            <a href="{{ route('users.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold hover:bg-slate-50">Batal</a>
            <x-ui.button type="submit">Simpan pengguna</x-ui.button>
        </div>
    </form>
@endsection
