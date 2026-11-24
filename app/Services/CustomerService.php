<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Piutang;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    /**
     * @return Collection<int, Customer>
     */
    public function cari(string $term): Collection
    {
        $term = trim($term);
        if ($term === '') {
            return new Collection;
        }

        return $this->queryWithRingkasan()
            ->where('aktif', true)
            ->where(function ($query) use ($term): void {
                $query->where('nama', 'like', "%{$term}%")
                    ->orWhere('telepon', 'like', "%{$term}%");
            })
            ->orderBy('nama')
            ->limit(10)
            ->get(['id', 'kode', 'nama', 'telepon', 'batas_hutang']);
    }

    public function denganRingkasan(Customer $customer): Customer
    {
        return $this->queryWithRingkasan()
            ->whereKey($customer->id)
            ->firstOrFail();
    }

    /**
     * @param  array{nama: string, telepon?: ?string, alamat?: ?string}  $data
     * @return array{customer: Customer, duplicate: bool}
     */
    public function buatCepat(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $name = trim($data['nama']);
            $normalizedName = mb_strtolower($name, 'UTF-8');
            $normalizedPhone = $this->normalizePhone($data['telepon'] ?? null);

            $duplicate = Customer::query()
                ->where('aktif', true)
                ->whereRaw('LOWER(nama) = ?', [$normalizedName])
                ->get(['id', 'kode', 'nama', 'telepon'])
                ->first(fn (Customer $customer): bool => $this->normalizePhone($customer->telepon) === $normalizedPhone);

            if ($duplicate !== null) {
                return ['customer' => $duplicate, 'duplicate' => true];
            }

            $customer = Customer::query()->create([
                'nama' => $name,
                'telepon' => isset($data['telepon']) ? trim($data['telepon']) : null,
                'alamat' => isset($data['alamat']) ? trim($data['alamat']) : null,
                'batas_hutang' => 0,
                'aktif' => true,
            ]);

            return ['customer' => $customer, 'duplicate' => false];
        });
    }

    private function normalizePhone(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone) ?? '';
    }

    private function queryWithRingkasan()
    {
        return Customer::query()
            ->withSum(
                ['piutangs as total_hutang' => fn ($query) => $query->where('status', Piutang::STATUS_UNPAID)],
                'sisa',
            )
            ->withExists([
                'piutangs as ada_piutang_terlambat' => fn ($query) => $query
                    ->where('status', Piutang::STATUS_UNPAID)
                    ->whereNotNull('jatuh_tempo')
                    ->whereDate('jatuh_tempo', '<', today()),
            ]);
    }
}
