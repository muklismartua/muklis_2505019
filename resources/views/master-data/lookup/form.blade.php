@extends('layouts.app')

@section('title', ($record ? 'Edit ' : 'Tambah ').$title)

@section('content')
    <div class="mb-6">
        <a href="{{ route($routeName.'.index') }}" class="text-sm font-medium text-indigo-700 hover:underline">&larr; Kembali ke {{ strtolower($title) }}</a>
        <h1 class="mt-3 text-2xl font-bold">{{ $record ? 'Edit ' : 'Tambah ' }}{{ strtolower($title) }}</h1>
    </div>

    <form method="POST" action="{{ $record ? route($routeName.'.update', $record) : route($routeName.'.store') }}" class="max-w-2xl space-y-5 rounded-lg border border-slate-200 bg-white p-6">
        @csrf
        @if ($record)
            @method('PUT')
        @endif

        @foreach ($fields as $field)
            @php($value = old($field['name'], $record?->{$field['name']}))
            @if (($field['type'] ?? 'text') === 'textarea')
                <div>
                    <label for="{{ $field['name'] }}" class="mb-1 block text-sm font-medium text-slate-700">{{ $field['label'] }}</label>
                    <textarea id="{{ $field['name'] }}" name="{{ $field['name'] }}" rows="3" maxlength="{{ $field['maxlength'] ?? 2000 }}" class="w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ $value }}</textarea>
                    @error($field['name'])<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
            @else
                <x-ui.input
                    :name="$field['name']"
                    :label="$field['label']"
                    :type="$field['type'] ?? 'text'"
                    :value="$value"
                    :required="$field['required'] ?? false"
                    :maxlength="$field['maxlength'] ?? 255"
                />
            @endif
        @endforeach

        <div class="flex justify-end gap-3">
            <a href="{{ route($routeName.'.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold hover:bg-slate-50">Batal</a>
            <x-ui.button type="submit">{{ $record ? 'Simpan perubahan' : 'Simpan' }}</x-ui.button>
        </div>
    </form>
@endsection
