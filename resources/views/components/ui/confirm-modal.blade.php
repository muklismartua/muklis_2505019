@props([
    'formId',
    'title' => 'Konfirmasi penghapusan',
    'confirmText' => 'Hapus',
])

<div x-data="{ open: false }" @keydown.escape.window="open = false">
    <span @click="open = true">{{ $trigger }}</span>

    <div
        x-cloak
        x-show="open"
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="{{ $attributes->get('id', 'confirm-modal-title') }}"
    >
        <button type="button" class="absolute inset-0 bg-slate-950/50" aria-label="Tutup dialog" @click="open = false"></button>
        <div class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-xl" @click.stop>
            <h2 id="{{ $attributes->get('id', 'confirm-modal-title') }}" class="text-lg font-semibold text-slate-900">{{ $title }}</h2>
            <div class="mt-2 text-sm text-slate-600">{{ $slot }}</div>
            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button variant="secondary" @click="open = false">Batal</x-ui.button>
                <x-ui.button :form="$formId" type="submit" variant="danger">{{ $confirmText }}</x-ui.button>
            </div>
        </div>
    </div>
</div>
