@extends('layouts.app')

@section('title', $title)

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold">{{ $title }}</h1>
            <p class="mt-1 text-sm text-slate-600">Kelola data {{ strtolower($title) }}.</p>
        </div>
        @can($routeName === 'categories' ? 'kategori.kelola' : ($routeName === 'units' ? 'satuan.kelola' : ($routeName === 'suppliers' ? 'pemasok.kelola' : 'pelanggan.kelola')))
            <a href="{{ route($routeName.'.create') }}" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                Tambah {{ strtolower($title) }}
            </a>
        @endcan
    </div>

    <form method="GET" action="{{ route($routeName.'.index') }}" class="mb-5 flex items-end gap-3 rounded-lg border border-slate-200 bg-white p-4">
        <div class="min-w-0 flex-1">
            <x-ui.input name="q" label="Cari {{ strtolower($title) }}" label-class="sr-only" :value="request('q')" placeholder="Cari {{ strtolower($title) }}..." />
        </div>
        <x-ui.button type="submit" variant="secondary">Cari</x-ui.button>
    </form>

    <x-ui.table :headers="array_merge(collect($columns)->pluck('label')->all(), $canManage ? ['Aksi'] : [])" :paginator="$records">
        @forelse ($records as $record)
            <tr>
                @foreach ($columns as $column)
                    <td class="max-w-sm px-5 py-4">
                        @if ($loop->first)
                            <a href="{{ route($routeName.'.show', $record) }}" class="font-medium text-indigo-700 hover:underline">{{ data_get($record, $column['name']) ?: '—' }}</a>
                        @elseif ($column['name'] === 'batas_hutang')
                            {{ (int) data_get($record, $column['name']) === 0 ? 'Tanpa batas' : rupiah((int) data_get($record, $column['name'])) }}
                        @elseif ($column['name'] === 'aktif')
                            <span @class([
                                'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                'bg-emerald-100 text-emerald-800' => data_get($record, $column['name']),
                                'bg-slate-100 text-slate-700' => ! data_get($record, $column['name']),
                            ])>{{ data_get($record, $column['name']) ? 'Aktif' : 'Nonaktif' }}</span>
                        @else
                            {{ data_get($record, $column['name']) ?: '—' }}
                        @endif
                    </td>
                @endforeach
                @if ($canManage)
                    <td class="whitespace-nowrap px-5 py-4 text-right">
                        <div class="inline-flex items-center gap-3">
                            <a href="{{ route($routeName.'.edit', $record) }}" class="font-medium text-indigo-700 hover:underline">Edit</a>
                            @if ($routeName !== 'customers' || auth()->user()->hasRole('admin'))
                                @php($formId = 'delete-'.$routeName.'-'.$record->id)
                                <form id="{{ $formId }}" method="POST" action="{{ route($routeName.'.destroy', $record) }}">
                                    @csrf
                                    @method('DELETE')
                                </form>
                                <x-ui.confirm-modal :form-id="$formId" :id="$formId.'-title'" title="Hapus data?" confirm-text="Hapus">
                                    <x-slot:trigger>
                                        <button type="button" class="font-medium text-red-700 hover:underline">Hapus</button>
                                    </x-slot:trigger>
                                    Data {{ $record->nama }} hanya dapat dihapus jika belum digunakan.
                                </x-ui.confirm-modal>
                            @endif
                        </div>
                    </td>
                @endif
            </tr>
        @empty
            <tr><td colspan="{{ count($columns) + (int) $canManage }}" class="px-5 py-10 text-center text-slate-500">Belum ada data {{ strtolower($title) }}.</td></tr>
        @endforelse
    </x-ui.table>
@endsection
