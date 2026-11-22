@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'required' => false,
    'labelClass' => '',
])

@php
    $inputId = $attributes->get('id', $name);
    $inputValue = $type === 'password' ? null : old($name, $value);
@endphp

<div>
    <label for="{{ $inputId }}" @class(['mb-1 block text-sm font-medium text-slate-700', $labelClass])>
        {{ $label }}
        @if ($required)
            <span class="text-red-600" aria-hidden="true">*</span>
        @endif
    </label>
    <input
        {{ $attributes->merge([
            'id' => $inputId,
            'name' => $name,
            'type' => $type,
            'value' => $inputValue,
        ])->class([
            'w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500',
            'border-red-300 focus:border-red-500 focus:ring-red-500' => $errors->has($name),
        ]) }}
        @if ($required) required @endif
        @if ($errors->has($name)) aria-invalid="true" aria-describedby="{{ $inputId }}-error" @endif
    >
    @error($name)
        <p id="{{ $inputId }}-error" class="mt-1 text-sm text-red-700">{{ $message }}</p>
    @enderror
</div>
