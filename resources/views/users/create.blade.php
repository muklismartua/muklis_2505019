@extends('layouts.transaction')

@section('title', 'Tambah Pengguna')

@section('content')
    <div class="mb-6">
        <a href="{{ route('users.index') }}" class="text-sm font-medium text-indigo-700 hover:underline">&larr; Kembali ke pengguna</a>
        <h1 class="mt-3 text-2xl font-bold">Tambah pengguna</h1>
    </div>

    <form method="POST" action="{{ route('users.store') }}" class="max-w-2xl space-y-5 rounded-lg border border-slate-200 bg-white p-6">
        @csrf
        <div>
            <label for="name" class="mb-1 block text-sm font-medium">Nama</label>
            <input id="name" name="name" value="{{ old('name') }}" required maxlength="255" class="w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div>
            <label for="email" class="mb-1 block text-sm font-medium">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required maxlength="255" class="w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div>
            <label for="role" class="mb-1 block text-sm font-medium">Role</label>
            <select id="role" name="role" required class="w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Pilih role</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->name }}" @selected(old('role') === $role->name)>{{ str($role->name)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="password" class="mb-1 block text-sm font-medium">Kata sandi</label>
            <input id="password" name="password" type="password" required autocomplete="new-password" class="w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div>
            <label for="password_confirmation" class="mb-1 block text-sm font-medium">Konfirmasi kata sandi</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div class="flex justify-end gap-3">
            <a href="{{ route('users.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold hover:bg-slate-50">Batal</a>
            <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Simpan pengguna</button>
        </div>
    </form>
@endsection
