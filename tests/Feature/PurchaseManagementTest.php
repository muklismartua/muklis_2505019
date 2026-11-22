<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use App\Services\NomorTransaksiService;
use App\Services\PembelianService;
use App\Services\StockService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class PurchaseManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->actingAs($this->admin);
    }

    public function test_purchase_saves_multiple_items_adds_stock_and_can_update_purchase_prices(): void
    {
        $supplier = Supplier::factory()->create();
        $first = Product::factory()->create(['sku' => 'BUY-001', 'harga_beli' => 1000, 'stok' => 2]);
        $second = Product::factory()->create(['sku' => 'BUY-002', 'harga_beli' => 2000, 'stok' => 1]);
        $data = [
            'supplier_id' => $supplier->id,
            'tanggal' => now()->toDateString(),
            'diskon' => 500,
            'metode_pembayaran' => 'transfer',
            'perbarui_harga_beli' => 1,
            'items' => [
                ['product_id' => $first->id, 'qty' => 2, 'harga_beli' => 1200],
                ['product_id' => $second->id, 'qty' => 3, 'harga_beli' => 1800],
            ],
        ];

        $this->get(route('purchases.create'))
            ->assertOk()
            ->assertSee('Tambah item')
            ->assertSee('purchaseForm(');
        $this->post(route('purchases.store'), $data)
            ->assertRedirect()
            ->assertSessionHas('success');

        $purchase = Purchase::query()->with('items')->firstOrFail();
        $this->assertSame('PB-'.now()->format('Ymd').'-0001', $purchase->nomor);
        $this->assertSame(7800, $purchase->subtotal);
        $this->assertSame(500, $purchase->diskon);
        $this->assertSame(7300, $purchase->total);
        $this->assertSame(2, $purchase->items->count());
        $this->assertSame(4, $first->fresh()->stok);
        $this->assertSame(4, $second->fresh()->stok);
        $this->assertSame(1200, $first->fresh()->harga_beli);
        $this->assertSame(1800, $second->fresh()->harga_beli);
        $this->assertDatabaseCount('stock_movements', 2);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $first->id,
            'tipe' => 'masuk',
            'qty' => 2,
            'referensi_type' => Purchase::class,
            'referensi_id' => $purchase->id,
        ]);

        $this->get(route('purchases.show', $purchase))
            ->assertOk()
            ->assertSee($purchase->nomor)
            ->assertSee($supplier->nama);
        $this->get(route('purchases.index', [
            'dari' => now()->toDateString(),
            'sampai' => now()->toDateString(),
            'supplier_id' => $supplier->id,
            'status' => 'selesai',
        ]))->assertOk()->assertSee($purchase->nomor);
        $this->delete(route('suppliers.destroy', $supplier))->assertSessionHasErrors('record');
    }

    public function test_purchase_numbers_increment_for_each_purchase_on_the_same_day(): void
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();
        $service = app(NomorTransaksiService::class);

        $firstNumber = $service->nomorPembelian();
        Purchase::query()->create([
            'nomor' => $firstNumber,
            'supplier_id' => $supplier->id,
            'user_id' => $this->admin->id,
            'tanggal' => now()->toDateString(),
            'subtotal' => 0,
            'diskon' => 0,
            'total' => 0,
            'metode_pembayaran' => 'tunai',
            'status' => Purchase::STATUS_COMPLETED,
        ]);

        $this->assertSame(
            'PB-'.now()->format('Ymd').'-0002',
            $service->nomorPembelian(),
        );
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_cancellation_reverses_stock_and_cannot_be_applied_twice(): void
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create(['stok' => 0]);
        $originalPurchasePrice = $product->harga_beli;
        $purchase = $this->createPurchase($supplier, $product, 5);
        $this->assertSame($originalPurchasePrice, $product->fresh()->harga_beli);

        $this->patch(route('purchases.cancel', $purchase))
            ->assertRedirect(route('purchases.show', $purchase))
            ->assertSessionHas('success');
        $this->assertSame(0, $product->fresh()->stok);
        $this->assertSame(Purchase::STATUS_CANCELLED, $purchase->fresh()->status);
        $this->assertDatabaseCount('stock_movements', 2);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'tipe' => 'keluar',
            'qty' => 5,
            'referensi_type' => Purchase::class,
            'referensi_id' => $purchase->id,
        ]);

        $this->patch(route('purchases.cancel', $purchase))->assertSessionHasErrors('status');
        $this->assertDatabaseCount('stock_movements', 2);
    }

    public function test_cancellation_is_rejected_without_partially_reversing_stock(): void
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create(['stok' => 0]);
        $purchase = $this->createPurchase($supplier, $product, 5);
        app(StockService::class)->kurangi($product, 3, null, 'Penjualan setelah pembelian', $this->admin);

        $this->patch(route('purchases.cancel', $purchase))->assertSessionHasErrors('purchase');

        $this->assertSame(2, $product->fresh()->stok);
        $this->assertSame(Purchase::STATUS_COMPLETED, $purchase->fresh()->status);
        $this->assertDatabaseCount('stock_movements', 2);
    }

    public function test_purchase_rolls_back_items_stock_and_movements_if_a_later_item_fails(): void
    {
        $supplier = Supplier::factory()->create();
        $first = Product::factory()->create(['sku' => 'ROLLBACK-1', 'stok' => 3]);
        $second = Product::factory()->create(['sku' => 'ROLLBACK-2', 'stok' => 4]);
        $realStockService = new StockService;
        $callCount = 0;
        $stockService = Mockery::mock(StockService::class);
        $stockService->shouldReceive('tambah')
            ->twice()
            ->andReturnUsing(function (...$arguments) use (&$callCount, $realStockService) {
                if (++$callCount === 2) {
                    throw new RuntimeException('Simulasi kegagalan pencatatan stok.');
                }

                return $realStockService->tambah(...$arguments);
            });
        $this->app->instance(StockService::class, $stockService);

        try {
            app(PembelianService::class)->simpan([
                'supplier_id' => $supplier->id,
                'tanggal' => now()->toDateString(),
                'diskon' => 0,
                'metode_pembayaran' => 'tunai',
                'items' => [
                    ['product_id' => $first->id, 'qty' => 2, 'harga_beli' => 1000],
                    ['product_id' => $second->id, 'qty' => 3, 'harga_beli' => 2000],
                ],
            ], $this->admin);
            $this->fail('Expected the simulated stock failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulasi kegagalan pencatatan stok.', $exception->getMessage());
        }

        $this->assertDatabaseCount('purchases', 0);
        $this->assertDatabaseCount('purchase_items', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertSame(3, $first->fresh()->stok);
        $this->assertSame(4, $second->fresh()->stok);
    }

    public function test_purchase_form_rejects_duplicate_products_and_discount_over_subtotal(): void
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create(['stok' => 0]);
        $base = [
            'supplier_id' => $supplier->id,
            'tanggal' => now()->toDateString(),
            'metode_pembayaran' => 'tunai',
        ];

        $this->from(route('purchases.create'))->post(route('purchases.store'), [
            ...$base,
            'items' => [
                ['product_id' => $product->id, 'qty' => 1, 'harga_beli' => 1000],
                ['product_id' => $product->id, 'qty' => 1, 'harga_beli' => 1000],
            ],
        ])->assertRedirect(route('purchases.create'))->assertSessionHasErrors();

        $this->from(route('purchases.create'))->post(route('purchases.store'), [
            ...$base,
            'diskon' => 1001,
            'items' => [['product_id' => $product->id, 'qty' => 1, 'harga_beli' => 1000]],
        ])->assertRedirect(route('purchases.create'))->assertSessionHasErrors('diskon');

        $this->assertDatabaseCount('purchases', 0);
        $this->assertDatabaseCount('purchase_items', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertSame(0, $product->fresh()->stok);
    }

    private function createPurchase(Supplier $supplier, Product $product, int $qty): Purchase
    {
        $this->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'tanggal' => now()->toDateString(),
            'diskon' => 0,
            'metode_pembayaran' => 'tunai',
            'items' => [['product_id' => $product->id, 'qty' => $qty, 'harga_beli' => 1000]],
        ])->assertRedirect();

        return Purchase::query()->latest('id')->firstOrFail();
    }
}
