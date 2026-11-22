@extends('layouts.app')

@section('title', 'Log Aktivitas')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Log Aktivitas</h1>
        <p class="mt-1 text-sm text-slate-600">Riwayat login, perubahan data, transaksi, dan penyesuaian stok.</p>
    </div>

    <form method="GET" action="{{ route('activity-logs.index') }}" class="mb-5 grid gap-4 rounded-lg border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-5">
        <div>
            <label for="tanggal_mulai" class="mb-1 block text-sm font-medium">Dari tanggal</label>
            <input id="tanggal_mulai" type="date" name="tanggal_mulai" value="{{ $filters['tanggal_mulai'] ?? '' }}" class="w-full rounded-md border-slate-300 text-sm">
        </div>
        <div>
            <label for="tanggal_akhir" class="mb-1 block text-sm font-medium">Sampai tanggal</label>
            <input id="tanggal_akhir" type="date" name="tanggal_akhir" value="{{ $filters['tanggal_akhir'] ?? '' }}" class="w-full rounded-md border-slate-300 text-sm">
        </div>
        <div>
            <label for="user_id" class="mb-1 block text-sm font-medium">Pengguna</label>
            <select id="user_id" name="user_id" class="w-full rounded-md border-slate-300 text-sm">
                <option value="">Semua pengguna</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected((string) ($filters['user_id'] ?? '') === (string) $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="aksi" class="mb-1 block text-sm font-medium">Jenis aktivitas</label>
            <select id="aksi" name="aksi" class="w-full rounded-md border-slate-300 text-sm">
                <option value="">Semua aktivitas</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}" @selected(($filters['aksi'] ?? '') === $action)>{{ str_replace(['.', '_'], [' · ', ' '], ucfirst($action)) }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-2">
            <x-ui.button type="submit">Filter</x-ui.button>
            <a href="{{ route('activity-logs.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold hover:bg-slate-50">Reset</a>
        </div>
    </form>

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Waktu</th>
                        <th class="px-4 py-3">Pengguna</th>
                        <th class="px-4 py-3">Aktivitas</th>
                        <th class="px-4 py-3">Deskripsi</th>
                        <th class="px-4 py-3">Alamat IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($logs as $log)
                        <tr>
                            <td class="whitespace-nowrap px-4 py-3">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                            <td class="px-4 py-3">{{ $log->user?->name ?? 'Pengguna dihapus' }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $log->aksi }}</td>
                            <td class="min-w-72 px-4 py-3">{{ $log->deskripsi }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $log->ip ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">Belum ada aktivitas yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 px-4 py-3">{{ $logs->links() }}</div>
    </div>
@endsection
