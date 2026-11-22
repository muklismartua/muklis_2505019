<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect(['Elektronik', 'Aksesori', 'Perlengkapan'])
            ->map(fn (string $nama) => Category::query()->firstOrCreate(['nama' => $nama]));

        $units = collect([
            ['nama' => 'Buah', 'singkatan' => 'bh'],
            ['nama' => 'Kotak', 'singkatan' => 'box'],
            ['nama' => 'Paket', 'singkatan' => 'pkt'],
        ])->map(fn (array $unit) => Unit::query()->firstOrCreate(['nama' => $unit['nama']], $unit));

        foreach (['CV Sumber Jaya', 'PT Mitra Niaga', 'UD Berkah'] as $index => $nama) {
            Supplier::query()->firstOrCreate(['nama' => $nama], [
                'telepon' => '08120000000'.($index + 1),
                'email' => 'supplier'.($index + 1).'@example.com',
                'alamat' => 'Jakarta',
            ]);
        }

        foreach (['Pelanggan Umum', 'Andi Saputra', 'Siti Aminah'] as $index => $nama) {
            Customer::query()->firstOrCreate(['nama' => $nama], [
                'telepon' => '08210000000'.($index + 1),
                'email' => 'pelanggan'.($index + 1).'@example.com',
                'alamat' => 'Jakarta',
            ]);
        }

        foreach (range(1, 10) as $number) {
            $sku = 'DEMO-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT);
            if (Product::query()->where('sku', $sku)->exists()) {
                continue;
            }

            Product::factory()->create([
                'sku' => $sku,
                'nama' => 'Produk Contoh '.$number,
                'category_id' => $categories[($number - 1) % $categories->count()]->id,
                'unit_id' => $units[($number - 1) % $units->count()]->id,
            ]);
        }
    }
}
