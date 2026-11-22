@props([
    'name',
    'label',
    'options' => [],
    'selected' => null,
    'placeholder' => 'Pilih opsi',
    'required' => false,
    'labelClass' => '',
])

@php
    $selectId = $attributes->get('id', $name);
    $selectedValue = old($name, $selected);
@endphp

<div>
    <label for="{{ $selectId }}" @class(['mb-1 block text-sm font-medium text-slate-700', $labelClass])>
        {{ $label }}
        @if ($required)
            <span class="text-red-600" aria-hidden="true">*</span>
        @endif
    </label>
    <select
        {{ $attributes->merge(['id' => $selectId, 'name' => $name])->class([
            'w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500',
            'border-red-300 focus:border-red-500 focus:ring-red-500' => $errors->has($name),
        ]) }}
        @if ($required) required @endif
        @if ($errors->has($name)) aria-invalid="true" aria-describedby="{{ $selectId }}-error" @endif
    >
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $value => $optionLabel)
            <option value="{{ $value }}" @selected((string) $selectedValue === (string) $value)>
                {{ $optionLabel }}
            </option>
        @endforeach
    </select>
    @error($name)
        <p id="{{ $selectId }}-error" class="mt-1 text-sm text-red-700">{{ $message }}</p>
    @enderror
</div>
