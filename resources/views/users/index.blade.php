@extends('layouts.app')

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

    <form method="GET" action="{{ route('users.index') }}" class="mb-5 flex items-end gap-3 rounded-lg border border-slate-200 bg-white p-4">
        <div class="min-w-0 flex-1">
            <x-ui.input name="q" label="Cari pengguna" label-class="sr-only" :value="request('q')" placeholder="Cari nama atau email..." />
        </div>
        <x-ui.button type="submit" variant="secondary">Cari</x-ui.button>
    </form>

    <x-ui.table :headers="['Nama', 'Email', 'Role', 'Status', 'Aksi']" :paginator="$users">
        @forelse ($users as $user)
            <tr>
                <td class="whitespace-nowrap px-5 py-4 font-medium">{{ $user->name }}</td>
                <td class="whitespace-nowrap px-5 py-4">{{ $user->email }}</td>
                <td class="whitespace-nowrap px-5 py-4">{{ $user->getRoleNames()->map(fn ($role) => str($role)->replace('_', ' ')->title())->implode(', ') ?: '—' }}</td>
                <td class="whitespace-nowrap px-5 py-4">
                    <x-ui.badge :status="$user->is_active ? 'active' : 'inactive'">
                        {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                    </x-ui.badge>
                </td>
                <td class="whitespace-nowrap px-5 py-4 text-right">
                    <div class="inline-flex items-center gap-3">
                        <a href="{{ route('users.edit', $user) }}" class="font-medium text-indigo-700 hover:underline">Edit</a>
                        @if (auth()->id() !== $user->id && $user->is_active)
                            @php($formId = 'deactivate-user-'.$user->id)
                            <form id="{{ $formId }}" method="POST" action="{{ route('users.activation', $user) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="is_active" value="0">
                            </form>
                            <x-ui.confirm-modal :form-id="$formId" :id="'deactivate-user-title-'.$user->id" title="Nonaktifkan pengguna?" confirm-text="Nonaktifkan">
                                <x-slot:trigger>
                                    <button type="button" class="font-medium text-red-700 hover:underline">Nonaktifkan</button>
                                </x-slot:trigger>
                                Akun {{ $user->name }} tidak dapat masuk setelah dinonaktifkan.
                            </x-ui.confirm-modal>
                        @elseif (auth()->id() !== $user->id)
                            <form method="POST" action="{{ route('users.activation', $user) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="is_active" value="1">
                                <x-ui.button type="submit" variant="ghost" class="px-0 py-0 text-green-700 shadow-none">Aktifkan</x-ui.button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">Tidak ada pengguna.</td></tr>
        @endforelse
    </x-ui.table>
@endsection
