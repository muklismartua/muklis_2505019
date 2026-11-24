@extends('layouts.app')

@section('title', 'Pengaturan Toko')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Pengaturan Toko</h1>
        <p class="mt-1 text-sm text-slate-600">Informasi ini digunakan pada invoice, nota, dan transaksi baru.</p>
    </div>

    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PATCH')

        <section class="grid gap-5 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-2">
            <x-ui.input name="nama_toko" label="Nama toko" :value="$settings['nama_toko']" required />
            <x-ui.input name="telepon" label="Telepon" :value="$settings['telepon']" />
            <div class="sm:col-span-2">
                <label for="alamat" class="mb-1 block text-sm font-medium text-slate-700">Alamat</label>
                <textarea id="alamat" name="alamat" rows="3" maxlength="2000" class="w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('alamat', $settings['alamat']) }}</textarea>
                @error('alamat')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>

            <div class="sm:col-span-2">
                <label for="logo" class="mb-1 block text-sm font-medium text-slate-700">Logo toko (JPG, PNG, WebP; maks. 2 MB)</label>
                <input id="logo" name="logo" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="block w-full text-sm text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold">
                @error('logo')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                @if ($logoUrl)
                    <div class="mt-3 flex items-center gap-4">
                        <img src="{{ $logoUrl }}" alt="Logo toko saat ini" class="max-h-20 max-w-40 rounded border border-slate-200 p-1">
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="hapus_logo" value="1" class="rounded border-slate-300 text-red-600 focus:ring-red-500">
                            Hapus logo saat ini
                        </label>
                    </div>
                @endif
            </div>
        </section>

        <section class="grid gap-5 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-2">
            <div>
                <x-ui.input name="prefix_pembelian" label="Prefix nomor pembelian" :value="$settings['prefix_pembelian']" maxlength="10" pattern="[A-Z0-9]+" required />
                <p class="mt-1 text-xs text-slate-500">Contoh: PB menghasilkan PB-YYYYMMDD-0001.</p>
            </div>
            <div>
                <x-ui.input name="prefix_penjualan" label="Prefix nomor penjualan" :value="$settings['prefix_penjualan']" maxlength="10" pattern="[A-Z0-9]+" required />
                <p class="mt-1 text-xs text-slate-500">Contoh: PJ menghasilkan PJ-YYYYMMDD-0001.</p>
            </div>
            <div>
                <x-ui.input
                    name="persentase_pajak_default"
                    label="Persentase pajak default (%)"
                    type="number"
                    min="0"
                    max="100"
                    step="0.01"
                    :value="$settings['persentase_pajak_default']"
                    required
                />
                <p class="mt-1 text-xs text-slate-500">Dipakai sebagai nilai awal pada formulir penjualan baru.</p>
            </div>
            <div>
                <x-ui.input
                    name="hari_jatuh_tempo_default"
                    label="Jatuh tempo hutang default (hari)"
                    type="number"
                    min="1"
                    max="365"
                    step="1"
                    :value="$settings['hari_jatuh_tempo_default']"
                />
                <p class="mt-1 text-xs text-slate-500">Jangka waktu setelah hari ini untuk penjualan Hutang (Bayar Nanti).</p>
            </div>
        </section>

        <div class="flex justify-end">
            <x-ui.button type="submit">Simpan pengaturan</x-ui.button>
        </div>
    </form>
@endsection
