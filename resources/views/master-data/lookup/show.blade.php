@extends('layouts.app')

@section('title', 'Detail '.$title)

@section('content')
    <div class="mb-6">
        <a href="{{ route($routeName.'.index') }}" class="text-sm font-medium text-indigo-700 hover:underline">&larr; Kembali ke {{ strtolower($title) }}</a>
        <div class="mt-3 flex items-start justify-between gap-4">
            <h1 class="text-2xl font-bold">Detail {{ strtolower($title) }}</h1>
            <a href="{{ route($routeName.'.edit', $record) }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Edit</a>
        </div>
    </div>
    <dl class="max-w-3xl divide-y divide-slate-100 rounded-lg border border-slate-200 bg-white px-5">
        @foreach ($fields as $field)
            <div class="grid gap-1 py-4 sm:grid-cols-3">
                <dt class="text-sm font-medium text-slate-500">{{ $field['label'] }}</dt>
                <dd class="whitespace-pre-line text-sm text-slate-900 sm:col-span-2">{{ $record->{$field['name']} ?: '—' }}</dd>
            </div>
        @endforeach
    </dl>
@endsection
