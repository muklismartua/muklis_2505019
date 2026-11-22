<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold text-slate-900">Dashboard</h1>
    </x-slot>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach (range(1, 4) as $card)
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm" aria-label="Kartu ringkasan {{ $card }}">
                <span class="sr-only">Ringkasan akan ditampilkan pada tahap 9.</span>
                <div class="h-3 w-2/5 rounded bg-slate-100"></div>
                <div class="mt-5 h-8 w-1/2 rounded bg-slate-100"></div>
                <div class="mt-4 h-3 w-3/4 rounded bg-slate-50"></div>
            </section>
        @endforeach
    </div>
</x-app-layout>
