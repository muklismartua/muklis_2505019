<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class StoreSettingsService
{
    public function all(): array
    {
        return array_merge(
            $this->defaults(),
            Setting::query()->pluck('value', 'key')->all(),
        );
    }

    public function get(string $key): string
    {
        return (string) ($this->all()[$key] ?? '');
    }

    public function forDocument(): array
    {
        $settings = $this->all();
        $storedLogo = Setting::query()->whereKey('logo')->value('value');
        $logo = $storedLogo === null
            ? config('store.logo')
            : ($storedLogo !== '' ? Storage::disk('public')->path($storedLogo) : null);

        return [
            'name' => $settings['nama_toko'],
            'address' => $settings['alamat'],
            'phone' => $settings['telepon'],
            'logo' => $logo,
        ];
    }

    public function logoUrl(): ?string
    {
        $storedLogo = Setting::query()->whereKey('logo')->value('value');

        if ($storedLogo !== null) {
            return $storedLogo !== '' ? Storage::disk('public')->url($storedLogo) : null;
        }

        $configuredLogo = config('store.logo');
        if (! is_string($configuredLogo) || ! is_file($configuredLogo)) {
            return null;
        }

        $publicPath = rtrim(public_path(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        if (! str_starts_with(strtolower($configuredLogo), strtolower($publicPath))) {
            return null;
        }

        return asset(str_replace(DIRECTORY_SEPARATOR, '/', substr($configuredLogo, strlen($publicPath))));
    }

    public function update(array $data): void
    {
        $oldLogo = $this->get('logo');
        $removeLogo = filter_var($data['hapus_logo'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $newLogo = null;

        if (($data['logo'] ?? null) instanceof UploadedFile) {
            $newLogo = $data['logo']->storePublicly('settings/logos', 'public');

            if ($newLogo === false) {
                throw new RuntimeException('Logo toko tidak dapat disimpan.');
            }
        }

        try {
            DB::transaction(function () use ($data, $newLogo, $removeLogo): void {
                foreach ([
                    'nama_toko',
                    'alamat',
                    'telepon',
                    'prefix_pembelian',
                    'prefix_penjualan',
                    'persentase_pajak_default',
                ] as $key) {
                    Setting::query()->updateOrCreate(
                        ['key' => $key],
                        ['value' => (string) ($data[$key] ?? '')],
                    );
                }

                if ($newLogo !== null || $removeLogo) {
                    Setting::query()->updateOrCreate(
                        ['key' => 'logo'],
                        ['value' => $newLogo ?? ''],
                    );
                }
            });
        } catch (Throwable $exception) {
            if ($newLogo !== null) {
                Storage::disk('public')->delete($newLogo);
            }

            throw $exception;
        }

        if ($oldLogo !== '' && ($newLogo !== null || $removeLogo) && $oldLogo !== $newLogo) {
            Storage::disk('public')->delete($oldLogo);
        }
    }

    private function defaults(): array
    {
        return [
            'nama_toko' => (string) config('store.name'),
            'alamat' => (string) config('store.address'),
            'telepon' => (string) config('store.phone'),
            'logo' => '',
            'prefix_pembelian' => (string) config('store.prefix_pembelian', 'PB'),
            'prefix_penjualan' => (string) config('store.prefix_penjualan', 'PJ'),
            'persentase_pajak_default' => (string) config('store.default_tax_rate', '0'),
        ];
    }
}
