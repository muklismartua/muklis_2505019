<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleManagementTest extends TestCase
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

    public function test_cash_sale_single_item_saves_reduces_stock_and_uses_server_total(): void
    {
        $product = Product::factory()->create(['harga_jual' => 90000, 'stok' => 4]);

        $this->post(route('sales.store'), [
            'tanggal' => now()->toDateString(),
            'diskon' => 0,
            'pajak_persen' => 3,
            'pajak' => 2695,
            'bayar' => 92700,
            'metode_pembayaran' => 'tunai',
            'items' => [['product_id' => $product->id, 'qty' => 1, 'diskon' => 0]],
        ])->assertRedirect()->assertSessionHas('success');

        $sale = Sale::query()->firstOrFail();
        $this->assertSame(92700, $sale->total);
        $this->assertSame(2700, $sale->pajak);
        $this->assertSame(3, $product->fresh()->stok);
    }

    public function test_cash_sale_rejects_payment_below_server_total(): void
    {
        $product = Product::factory()->create(['harga_jual' => 90000, 'stok' => 4]);

        $this->from(route('sales.create'))->post(route('sales.store'), [
            'tanggal' => now()->toDateString(),
            'pajak_persen' => 3,
            'bayar' => 92699,
            'metode_pembayaran' => 'tunai',
            'items' => [['product_id' => $product->id, 'qty' => 1, 'diskon' => 0]],
        ])->assertRedirect(route('sales.create'))->assertSessionHasErrors('bayar');
    }

    public function test_empty_cart_is_rejected(): void
    {
        $this->from(route('sales.create'))->post(route('sales.store'), [
            'tanggal' => now()->toDateString(),
            'bayar' => 0,
            'metode_pembayaran' => 'tunai',
            'items' => [],
        ])->assertRedirect(route('sales.create'))->assertSessionHasErrors('items');
    }

    public function test_tempo_sale_without_customer_is_rejected(): void
    {
        $product = Product::factory()->create(['harga_jual' => 90000, 'stok' => 4]);

        $this->from(route('sales.create'))->post(route('sales.store'), [
            'tanggal' => now()->toDateString(),
            'bayar' => 0,
            'metode_pembayaran' => 'tempo',
            'jatuh_tempo' => now()->addDays(14)->toDateString(),
            'items' => [['product_id' => $product->id, 'qty' => 1, 'diskon' => 0]],
        ])->assertRedirect(route('sales.create'))->assertSessionHasErrors('customer_id');
    }

    public function test_sale_saves_multiple_items_deducts_stock_and_calculates_totals(): void
    {
        $customer = Customer::factory()->create();
        $first = Product::factory()->create([
            'sku' => 'SALE-001',
            'nama' => 'Produk Pertama',
            'harga_jual' => 1500,
            'stok' => 5,
        ]);
        $second = Product::factory()->create([
            'sku' => 'SALE-002',
            'nama' => 'Produk Kedua',
            'harga_jual' => 2000,
            'stok' => 4,
        ]);

        $this->get(route('sales.create'))
            ->assertOk()
            ->assertSee('Tambah produk ke keranjang')
            ->assertSee('Pilih produk untuk ditambahkan')
            ->assertSee('saleForm(');
        $this->post(route('sales.store'), [
            'customer_id' => $customer->id,
            'tanggal' => now()->toDateString(),
            'diskon' => 200,
            'pajak' => 500,
            'bayar' => 6000,
            'metode_pembayaran' => 'tunai',
            'items' => [
                ['product_id' => $first->id, 'qty' => 2, 'diskon' => 100],
                ['product_id' => $second->id, 'qty' => 1, 'diskon' => 0],
            ],
        ])->assertRedirect()->assertSessionHas('success');

        $sale = Sale::query()->with('items')->firstOrFail();
        $this->assertSame('PJ-'.now()->format('Ymd').'-0001', $sale->nomor);
        $this->assertSame(4900, $sale->subtotal);
        $this->assertSame(200, $sale->diskon);
        $this->assertSame(500, $sale->pajak);
        $this->assertSame(5200, $sale->total);
        $this->assertSame(800, $sale->kembalian);
        $this->assertSame(2, $sale->items->count());
        $this->assertSame(3, $first->fresh()->stok);
        $this->assertSame(3, $second->fresh()->stok);
        $this->assertDatabaseCount('stock_movements', 2);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $first->id,
            'tipe' => 'keluar',
            'qty' => 2,
            'referensi_type' => Sale::class,
            'referensi_id' => $sale->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'aksi' => 'penjualan.dibuat',
            'subject_type' => Sale::class,
            'subject_id' => $sale->id,
        ]);

        $this->get(route('sales.show', $sale))
            ->assertOk()
            ->assertSee($sale->nomor)
            ->assertSee($customer->nama);
        $this->get(route('sales.index', [
            'dari' => now()->toDateString(),
            'sampai' => now()->toDateString(),
            'customer_id' => $customer->id,
            'status' => 'selesai',
            'metode_pembayaran' => 'tunai',
        ]))->assertOk()->assertSee($sale->nomor);
    }

    public function test_sale_number_increments_daily_and_tempo_can_be_partially_paid(): void
    {
        $product = Product::factory()->create(['harga_jual' => 2500, 'stok' => 3]);
        $customer = Customer::factory()->create();
        $this->createSale($product, 1, [
            'metode_pembayaran' => 'tempo',
            'customer_id' => $customer->id,
            'bayar' => 1000,
        ]);
        $this->createSale($product, 1, [
            'metode_pembayaran' => 'tempo',
            'customer_id' => $customer->id,
            'bayar' => 0,
        ]);

        $sales = Sale::query()->orderBy('id')->get();
        $this->assertSame('PJ-'.now()->format('Ymd').'-0001', $sales[0]->nomor);
        $this->assertSame('PJ-'.now()->format('Ymd').'-0002', $sales[1]->nomor);
        $this->assertSame(0, $sales[0]->kembalian);
        $this->assertSame(0, $sales[1]->kembalian);
        $this->assertDatabaseHas('receivables', [
            'sale_id' => $sales[0]->id,
            'customer_id' => $customer->id,
            'total' => 2500,
            'terbayar' => 1000,
            'sisa' => 1500,
            'status' => 'sebagian',
        ]);
        $this->assertDatabaseHas('receivables', [
            'sale_id' => $sales[1]->id,
            'customer_id' => $customer->id,
            'sisa' => 2500,
            'status' => 'belum_lunas',
        ]);
        $this->get(route('sales.show', $sales[0]))
            ->assertOk()
            ->assertSee('Sisa piutang')
            ->assertSee('1.500');
        $this->get(route('sales.pdf', $sales[0]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_tempo_sale_requires_a_customer_and_cancellation_closes_its_receivable(): void
    {
        $product = Product::factory()->create(['harga_jual' => 2500, 'stok' => 3]);
        $base = [
            'tanggal' => now()->toDateString(),
            'diskon' => 0,
            'pajak' => 0,
            'bayar' => 500,
            'metode_pembayaran' => 'tempo',
            'items' => [['product_id' => $product->id, 'qty' => 1, 'diskon' => 0]],
        ];

        $this->from(route('sales.create'))
            ->post(route('sales.store'), $base)
            ->assertRedirect(route('sales.create'))
            ->assertSessionHasErrors('customer_id');
        $this->assertDatabaseCount('sales', 0);

        $customer = Customer::factory()->create();
        $sale = $this->createSale($product, 1, [
            'customer_id' => $customer->id,
            'metode_pembayaran' => 'tempo',
            'bayar' => 500,
        ]);

        $this->patch(route('sales.cancel', $sale))->assertRedirect();
        $this->assertDatabaseHas('receivables', [
            'sale_id' => $sale->id,
            'sisa' => 0,
            'status' => 'batal',
        ]);
    }

    public function test_percentage_tax_rounds_to_nearest_rupiah_on_server(): void
    {
        $product = Product::factory()->create(['harga_jual' => 90000, 'stok' => 10]);

        $response = $this->post(route('sales.store'), [
            'tanggal' => now()->toDateString(),
            'metode_pembayaran' => 'tunai',
            'diskon' => 0,
            'pajak_persen' => 3,
            'pajak' => 2695,
            'bayar' => 92700,
            'items' => [[
                'product_id' => $product->id,
                'qty' => 1,
                'diskon' => 0,
            ]],
        ]);

        $sale = Sale::query()->firstOrFail();
        $response->assertRedirect(route('sales.show', $sale));
        $this->assertSame(2700, $sale->pajak);
        $this->assertSame(92700, $sale->total);
    }

    public function test_percentage_tax_is_calculated_on_the_discounted_subtotal_on_the_server(): void
    {
        $product = Product::factory()->create(['harga_jual' => 10000, 'stok' => 2]);

        $this->post(route('sales.store'), [
            'tanggal' => now()->toDateString(),
            'diskon' => 1000,
            'pajak' => 9999,
            'pajak_persen' => 11,
            'bayar' => 9990,
            'metode_pembayaran' => 'tunai',
            'items' => [['product_id' => $product->id, 'qty' => 1, 'diskon' => 0]],
        ])->assertRedirect()->assertSessionHas('success');

        $sale = Sale::query()->firstOrFail();
        $this->assertSame(990, $sale->pajak);
        $this->assertSame(9990, $sale->total);
    }

    public function test_insufficient_stock_rolls_back_the_entire_multi_item_sale(): void
    {
        $first = Product::factory()->create(['nama' => 'Stok Cukup', 'harga_jual' => 1000, 'stok' => 5]);
        $second = Product::factory()->create(['nama' => 'Stok Terbatas', 'harga_jual' => 1000, 'stok' => 1]);

        $this->from(route('sales.create'))->post(route('sales.store'), [
            'tanggal' => now()->toDateString(),
            'diskon' => 0,
            'pajak' => 0,
            'bayar' => 100000,
            'metode_pembayaran' => 'tunai',
            'items' => [
                ['product_id' => $first->id, 'qty' => 2, 'diskon' => 0],
                ['product_id' => $second->id, 'qty' => 2, 'diskon' => 0],
            ],
        ])->assertRedirect(route('sales.create'))->assertSessionHasErrors('items');

        $errors = session('errors')->get('items');
        $this->assertStringContainsString('Stok Terbatas', implode(' ', $errors));
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_items', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertSame(5, $first->fresh()->stok);
        $this->assertSame(1, $second->fresh()->stok);
    }

    public function test_cancellation_restores_stock_and_cannot_be_applied_twice(): void
    {
        $product = Product::factory()->create(['stok' => 5]);
        $sale = $this->createSale($product, 3);

        $this->patch(route('sales.cancel', $sale))
            ->assertRedirect(route('sales.show', $sale))
            ->assertSessionHas('success');

        $this->assertSame(5, $product->fresh()->stok);
        $this->assertSame(Sale::STATUS_CANCELLED, $sale->fresh()->status);
        $this->assertDatabaseCount('stock_movements', 2);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'tipe' => 'masuk',
            'qty' => 3,
            'referensi_type' => Sale::class,
            'referensi_id' => $sale->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'aksi' => 'penjualan.dibatalkan',
            'subject_type' => Sale::class,
            'subject_id' => $sale->id,
        ]);

        $this->patch(route('sales.cancel', $sale))->assertSessionHasErrors('status');
        $this->assertDatabaseCount('stock_movements', 2);
    }

    public function test_duplicate_products_discounts_and_insufficient_payment_are_rejected(): void
    {
        $product = Product::factory()->create(['harga_jual' => 1000, 'stok' => 5]);
        $base = [
            'tanggal' => now()->toDateString(),
            'diskon' => 0,
            'pajak' => 0,
            'bayar' => 1000,
            'metode_pembayaran' => 'tunai',
        ];

        $this->from(route('sales.create'))->post(route('sales.store'), [
            ...$base,
            'items' => [
                ['product_id' => $product->id, 'qty' => 1, 'diskon' => 0],
                ['product_id' => $product->id, 'qty' => 1, 'diskon' => 0],
            ],
        ])->assertRedirect(route('sales.create'))->assertSessionHasErrors();

        $this->from(route('sales.create'))->post(route('sales.store'), [
            ...$base,
            'bayar' => 999,
            'items' => [['product_id' => $product->id, 'qty' => 1, 'diskon' => 0]],
        ])->assertRedirect(route('sales.create'))->assertSessionHasErrors('bayar');

        $this->from(route('sales.create'))->post(route('sales.store'), [
            ...$base,
            'items' => [['product_id' => $product->id, 'qty' => 1, 'diskon' => 1001]],
        ])->assertRedirect(route('sales.create'))->assertSessionHasErrors('items.0.diskon');

        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertSame(5, $product->fresh()->stok);
    }

    public function test_tempo_down_payment_cannot_exceed_total_and_due_date_must_be_in_future(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['harga_jual' => 1000, 'stok' => 3]);
        $base = [
            'customer_id' => $customer->id,
            'tanggal' => now()->toDateString(),
            'diskon' => 0,
            'pajak' => 0,
            'metode_pembayaran' => 'tempo',
            'items' => [['product_id' => $product->id, 'qty' => 1]],
        ];

        $this->from(route('sales.create'))
            ->post(route('sales.store'), [
                ...$base,
                'jatuh_tempo' => now()->addDay()->toDateString(),
                'bayar' => 1001,
            ])
            ->assertRedirect(route('sales.create'))
            ->assertSessionHasErrors('bayar');

        $this->from(route('sales.create'))
            ->post(route('sales.store'), [
                ...$base,
                'jatuh_tempo' => now()->toDateString(),
                'bayar' => 0,
            ])
            ->assertRedirect(route('sales.create'))
            ->assertSessionHasErrors('jatuh_tempo');

        $this->assertDatabaseCount('sales', 0);
        $this->assertSame(3, $product->fresh()->stok);
    }

    private function createSale(Product $product, int $qty, array $overrides = []): Sale
    {
        $data = array_merge([
            'tanggal' => now()->toDateString(),
            'diskon' => 0,
            'pajak' => 0,
            'bayar' => $product->harga_jual * $qty,
            'metode_pembayaran' => 'tunai',
            'items' => [['product_id' => $product->id, 'qty' => $qty, 'diskon' => 0]],
        ], $overrides);
        if ($data['metode_pembayaran'] === 'tempo') {
            $data['jatuh_tempo'] ??= now()->addDays(14)->toDateString();
        }

        $this->post(route('sales.store'), $data)->assertRedirect();

        return Sale::query()->latest('id')->firstOrFail();
    }
}
