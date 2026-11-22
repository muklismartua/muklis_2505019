@extends('layouts.transaction')

@section('title', 'Manajemen Pengguna')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold">Manajemen Pengguna</h1>
            <p class="mt-1 text-sm text-slate-600">Kelola akun, role, dan status akses pengguna.</p>
        </div>
        <a href="{{ route('users.create') }}" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
            Tambah pengguna
        </a>
    </div>

    <form method="GET" action="{{ route('users.index') }}" class="mb-5 flex gap-3 rounded-lg border border-slate-200 bg-white p-4">
        <label class="sr-only" for="q">Cari pengguna</label>
        <input id="q" name="q" value="{{ request('q') }}" placeholder="Cari nama atau email..." class="min-w-0 flex-1 rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
        <button class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50">Cari</button>
    </form>

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Nama</th>
                        <th class="px-5 py-3">Email</th>
                        <th class="px-5 py-3">Role</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        <tr>
                            <td class="whitespace-nowrap px-5 py-4 font-medium">{{ $user->name }}</td>
                            <td class="whitespace-nowrap px-5 py-4">{{ $user->email }}</td>
                            <td class="whitespace-nowrap px-5 py-4">{{ $user->getRoleNames()->map(fn ($role) => str($role)->replace('_', ' ')->title())->implode(', ') ?: '—' }}</td>
                            <td class="whitespace-nowrap px-5 py-4">
                                @if ($user->is_active)
                                    <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-800">Aktif</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">Nonaktif</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <div class="inline-flex items-center gap-3">
                                    <a href="{{ route('users.edit', $user) }}" class="font-medium text-indigo-700 hover:underline">Edit</a>
                                    @if (auth()->id() !== $user->id)
                                        <form method="POST" action="{{ route('users.activation', $user) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="is_active" value="{{ $user->is_active ? '0' : '1' }}">
                                            <button type="submit" class="font-medium {{ $user->is_active ? 'text-red-700 hover:underline' : 'text-green-700 hover:underline' }}">
                                                {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">Tidak ada pengguna.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">{{ $users->links() }}</div>
        @endif
    </div>
@endsection
