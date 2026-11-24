<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\StockService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
    }

    public function test_stock_adjustment_page_uses_stock_service_and_low_stock_pages_render(): void
    {
        $product = Product::factory()->create([
            'nama' => 'Kopi Bubuk',
            'stok' => 8,
            'stok_minimum' => 5,
        ]);

        $this->get(route('stock.index'))->assertOk()->assertSee('Kopi Bubuk');
        $this->get(route('stock-adjustments.create'))->assertOk();
        $this->get(route('products.index'))->assertOk();

        $this->post(route('stock-adjustments.store'), [
            'product_id' => $product->id,
            'qty_fisik' => 4,
            'alasan' => 'Barang rusak saat pengecekan',
        ])->assertRedirect(route('stock-adjustments.index'))->assertSessionHas('success');

        $adjustment = StockAdjustment::query()->firstOrFail();
        $movement = StockMovement::query()->firstOrFail();
        $this->assertSame(4, $product->fresh()->stok);
        $this->assertSame($adjustment->id, $movement->referensi_id);
        $this->get(route('stock-adjustments.index'))->assertOk()->assertSee('Barang rusak saat pengecekan');
        $this->get(route('stock.low-stock'))->assertOk()->assertSee('Kopi Bubuk')->assertSee('Stok menipis');
        $this->get(route('products.index'))->assertOk()->assertSee('Stok menipis');
    }

    public function test_stock_card_shows_movements_and_filters_by_date(): void
    {
        $product = Product::factory()->create(['nama' => 'Teh Hijau', 'stok' => 4]);
        $movement = app(StockService::class)->tambah($product, 3, null, 'Restock', auth()->user());

        $this->get(route('stock.show', $product))
            ->assertOk()
            ->assertSee('Restock')
            ->assertSee((string) $movement->qty);
        $this->get(route('stock.show', ['product' => $product, 'dari' => now()->addDay()->toDateString()]))
            ->assertOk()
            ->assertSee('Belum ada riwayat pergerakan');
        $this->get(route('stock.show', ['product' => $product, 'dari' => now()->addDay()->toDateString(), 'sampai' => now()->toDateString()]))
            ->assertSessionHasErrors('sampai');
    }

    public function test_users_without_stock_permission_cannot_access_stock_pages(): void
    {
        $cashier = User::factory()->create();
        $cashier->assignRole('kasir');
        $this->actingAs($cashier);

        $this->get(route('stock.index'))->assertForbidden();
        $this->get(route('stock-adjustments.index'))->assertForbidden();
        $this->get(route('stock-manuals.index'))->assertForbidden();
        $this->get(route('stock-manuals.create'))->assertForbidden();
        $this->post(route('stock-manuals.store'), [])->assertForbidden();
        $this->post(route('stock-adjustments.store'), [])->assertForbidden();
    }
}
