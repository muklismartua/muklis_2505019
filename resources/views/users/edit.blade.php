@extends('layouts.app')

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
        <x-ui.input name="name" label="Nama" :value="old('name', $user->name)" required maxlength="255" />
        <x-ui.input name="email" label="Email" type="email" :value="old('email', $user->email)" required maxlength="255" />
        <x-ui.select name="role" label="Role" :options="$roles" :selected="old('role', $user->getRoleNames()->first())" :placeholder="null" required />
        <x-ui.input name="password" label="Kata sandi baru (opsional)" type="password" autocomplete="new-password" />
        <x-ui.input name="password_confirmation" label="Konfirmasi kata sandi baru" type="password" autocomplete="new-password" />
        <div class="flex justify-end gap-3">
            <a href="{{ route('users.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold hover:bg-slate-50">Batal</a>
            <x-ui.button type="submit">Simpan perubahan</x-ui.button>
        </div>
    </form>
@endsection
