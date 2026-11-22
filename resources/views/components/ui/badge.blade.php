@props([
    'status' => 'neutral',
])

@php
    $styles = [
        'active' => 'bg-green-100 text-green-800',
        'inactive' => 'bg-slate-100 text-slate-700',
        'success' => 'bg-green-100 text-green-800',
        'warning' => 'bg-amber-100 text-amber-800',
        'danger' => 'bg-red-100 text-red-800',
        'info' => 'bg-blue-100 text-blue-800',
        'neutral' => 'bg-slate-100 text-slate-700',
    ];
@endphp

<span {{ $attributes->class([
    'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium',
    $styles[$status] ?? $styles['neutral'],
]) }}>
    {{ $slot }}
</span>
