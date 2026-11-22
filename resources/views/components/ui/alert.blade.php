@props([
    'type' => 'info',
    'message' => null,
])

@php
    $styles = [
        'success' => 'border-green-200 bg-green-50 text-green-900',
        'error' => 'border-red-200 bg-red-50 text-red-900',
        'info' => 'border-blue-200 bg-blue-50 text-blue-900',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-900',
    ];
@endphp

<div
    role="{{ $type === 'error' ? 'alert' : 'status' }}"
    {{ $attributes->class([
        'rounded-md border px-4 py-3 text-sm',
        $styles[$type] ?? $styles['info'],
    ]) }}
>
    {{ $message ?? $slot }}
</div>
