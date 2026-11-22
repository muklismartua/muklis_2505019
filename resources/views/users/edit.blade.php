@extends('layouts.transaction')

@section('title', 'Edit Pengguna')

@section('content')
    <div class="mb-6">
        <a href="{{ route('users.index') }}" class="text-sm font-medium text-indigo-700 hover:underline">&larr; Kembali ke pengguna</a>
        <h1 class="mt-3 text-2xl font-bold">Edit pengguna</h1>
    </div>

    @unless ($user->is_active)
        <div class="mb-5 max-w-2xl rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            Akun ini nonaktif dan tidak dapat masuk sampai diaktifkan kembali.
        </div>
    @endunless

    <form method="POST" action="{{ route('users.update', $user) }}" class="max-w-2xl space-y-5 rounded-lg border border-slate-200 bg-white p-6">
        @csrf
        @method('PUT')
        <div>
            <label for="name" class="mb-1 block text-sm font-medium">Nama</label>
            <input id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="255" class="w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div>
            <label for="email" class="mb-1 block text-sm font-medium">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required maxlength="255" class="w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div>
            <label for="role" class="mb-1 block text-sm font-medium">Role</label>
            <select id="role" name="role" required class="w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach ($roles as $role)
                    <option value="{{ $role->name }}" @selected(old('role', $user->getRoleNames()->first()) === $role->name)>{{ str($role->name)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="password" class="mb-1 block text-sm font-medium">Kata sandi baru <span class="font-normal text-slate-500">(opsional)</span></label>
            <input id="password" name="password" type="password" autocomplete="new-password" class="w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div>
            <label for="password_confirmation" class="mb-1 block text-sm font-medium">Konfirmasi kata sandi baru</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div class="flex justify-end gap-3">
            <a href="{{ route('users.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold hover:bg-slate-50">Batal</a>
            <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Simpan perubahan</button>
        </div>
    </form>
@endsection
