<?php

namespace Tests\Feature;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_transaction_pages_render(): void
    {
        $this->get('/')->assertOk()->assertSee('Transaksi');
        $this->get(route('transactions.create'))->assertOk()->assertSee('Buat transaksi');
    }

    public function test_purchase_creates_multiple_items_and_records_stock_movements(): void
    {
        $keyboard = Product::query()->create([
            'sku' => 'KB-001',
            'name' => 'Keyboard',
            'selling_price' => 250000,
            'purchase_price' => 180000,
            'stock' => 4,
        ]);
        $mouse = Product::query()->create([
            'sku' => 'MS-001',
            'name' => 'Mouse',
            'selling_price' => 100000,
            'purchase_price' => 65000,
            'stock' => 10,
        ]);

        $response = $this->post(route('transactions.store'), [
            'type' => InventoryTransaction::TYPE_PURCHASE,
            'transaction_date' => '2026-11-20',
            'items' => [
                ['product_id' => $keyboard->id, 'quantity' => 3],
                ['product_id' => $mouse->id, 'quantity' => 5],
            ],
        ]);

        $transaction = InventoryTransaction::query()->firstOrFail();
        $response->assertRedirect(route('transactions.show', $transaction));
        $this->assertSame(865000, $transaction->total);
        $this->assertSame(7, $keyboard->fresh()->stock);
        $this->assertSame(15, $mouse->fresh()->stock);
        $this->assertDatabaseCount('inventory_transaction_items', 2);
        $this->assertDatabaseCount('stock_movements', 2);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $keyboard->id,
            'type' => 'masuk',
            'quantity' => 3,
            'stock_before' => 4,
            'stock_after' => 7,
        ]);
    }

    public function test_sale_with_insufficient_stock_rolls_back_all_items_and_movements(): void
    {
        $first = Product::query()->create([
            'sku' => 'A-001',
            'name' => 'Produk A',
            'selling_price' => 50000,
            'purchase_price' => 30000,
            'stock' => 10,
        ]);
        $second = Product::query()->create([
            'sku' => 'B-001',
            'name' => 'Produk B',
            'selling_price' => 75000,
            'purchase_price' => 45000,
            'stock' => 1,
        ]);

        $response = $this->from(route('transactions.create'))->post(route('transactions.store'), [
            'type' => InventoryTransaction::TYPE_SALE,
            'transaction_date' => '2026-11-20',
            'items' => [
                ['product_id' => $first->id, 'quantity' => 3],
                ['product_id' => $second->id, 'quantity' => 2],
            ],
        ]);

        $response->assertRedirect(route('transactions.create'));
        $response->assertSessionHasErrors('items');
        $this->assertSame(10, $first->fresh()->stock);
        $this->assertSame(1, $second->fresh()->stock);
        $this->assertDatabaseCount('inventory_transactions', 0);
        $this->assertDatabaseCount('inventory_transaction_items', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_sale_decreases_stock_and_uses_server_side_selling_price(): void
    {
        $product = Product::query()->create([
            'sku' => 'SP-001',
            'name' => 'Produk',
            'selling_price' => 125000,
            'purchase_price' => 80000,
            'stock' => 8,
        ]);

        $response = $this->post(route('transactions.store'), [
            'type' => InventoryTransaction::TYPE_SALE,
            'transaction_date' => '2026-11-20',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ]);

        $transaction = InventoryTransaction::query()->with('items')->firstOrFail();
        $response->assertRedirect(route('transactions.show', $transaction));
        $this->get(route('transactions.show', $transaction))
            ->assertOk()
            ->assertSee($transaction->transaction_number)
            ->assertSee('Produk');
        $this->assertSame(250000, $transaction->total);
        $this->assertSame(125000, $transaction->items->first()->unit_price);
        $this->assertSame(6, $product->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'keluar',
            'quantity' => 2,
            'stock_before' => 8,
            'stock_after' => 6,
        ]);
    }
}
