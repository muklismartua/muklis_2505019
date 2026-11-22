<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sku' => fake()->unique()->bothify('SKU-######'),
            'nama' => fake()->words(3, true),
            'category_id' => Category::factory(),
            'unit_id' => Unit::factory(),
            'harga_beli' => fake()->numberBetween(1000, 1000000),
            'harga_jual' => fake()->numberBetween(1000, 1500000),
            'stok' => 0,
            'stok_minimum' => fake()->numberBetween(0, 10),
            'aktif' => true,
        ];
    }
}
