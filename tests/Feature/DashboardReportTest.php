<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Services\DashboardService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_cashier_dashboard_only_includes_their_own_sales(): void
    {
        $cashier = User::factory()->create();
        $cashier->assignRole('kasir');
        $otherUser = User::factory()->create();
        $ownProduct = Product::factory()->create(['nama' => 'Produk Kasir', 'harga_jual' => 1000]);
        $otherProduct = Product::factory()->create(['nama' => 'Produk Lain', 'harga_jual' => 9000]);
        $ownSale = $this->createSale($cashier, $ownProduct, 1000, 'PJ-'.now()->format('Ymd').'-0101');
        $otherSale = $this->createSale($otherUser, $otherProduct, 9000, 'PJ-'.now()->format('Ymd').'-0102');

        $data = app(DashboardService::class)->data($cashier, 7);

        $this->assertSame(1000, $data['stats']['sales_today']);
        $this->assertSame(1000, $data['stats']['sales_this_month']);
        $this->assertSame(1, $data['stats']['transactions_today']);
        $this->assertCount(7, $data['trend']['labels']);
        $this->assertSame(1000, end($data['trend']['values']));
        $this->assertNull($data['stats']['low_stock']);
        $this->assertSame(['Produk Kasir'], $data['top_products']->pluck('nama')->all());
        $this->assertSame([$ownSale->nomor], $data['recent_transactions']->pluck('nomor')->all());
        $this->assertNotContains($otherSale->nomor, $data['recent_transactions']->pluck('nomor')->all());

        $this->actingAs($cashier)
            ->get(route('dashboard', ['hari' => 30]))
            ->assertOk()
            ->assertSee('Transaksi milik Anda')
            ->assertSee($ownSale->nomor)
            ->assertDontSee($otherSale->nomor);

        $thirtyDayData = app(DashboardService::class)->data($cashier, 30);
        $this->assertCount(30, $thirtyDayData['trend']['labels']);
        $this->assertSame(1000, end($thirtyDayData['trend']['values']));
    }

    public function test_owner_can_view_and_export_all_sqlite_reports(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('owner');
        $customer = Customer::factory()->create();
        $supplier = Supplier::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'nama' => 'Produk Laporan',
            'harga_beli' => 500,
            'harga_jual' => 1000,
            'stok' => 2,
            'stok_minimum' => 2,
        ]);
        $sale = Sale::query()->create([
            'nomor' => 'PJ-'.now()->format('Ymd').'-0201',
            'customer_id' => $customer->id,
            'user_id' => $owner->id,
            'tanggal' => now()->toDateString(),
            'subtotal' => 1800,
            'diskon' => 100,
            'pajak' => 200,
            'total' => 1900,
            'bayar' => 2000,
            'kembalian' => 100,
            'metode_pembayaran' => Sale::PAYMENT_CASH,
            'status' => Sale::STATUS_COMPLETED,
        ]);
        SaleItem::query()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'qty' => 2,
            'harga_jual' => 1000,
            'diskon' => 200,
            'subtotal' => 1800,
        ]);
        $purchase = Purchase::query()->create([
            'nomor' => 'PB-'.now()->format('Ymd').'-0201',
            'supplier_id' => $supplier->id,
            'user_id' => $owner->id,
            'tanggal' => now()->toDateString(),
            'subtotal' => 1000,
            'diskon' => 100,
            'total' => 900,
            'metode_pembayaran' => 'transfer',
            'status' => Purchase::STATUS_COMPLETED,
        ]);
        PurchaseItem::query()->create([
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'qty' => 2,
            'harga_beli' => 500,
            'subtotal' => 1000,
        ]);
        StockMovement::query()->create([
            'product_id' => $product->id,
            'tipe' => 'masuk',
            'qty' => 2,
            'stok_sebelum' => 0,
            'stok_sesudah' => 2,
            'referensi_type' => Purchase::class,
            'referensi_id' => $purchase->id,
            'keterangan' => 'Uji laporan',
            'user_id' => $owner->id,
        ]);
        $this->actingAs($owner);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Rp 1.900')
            ->assertSee('Produk Laporan');

        $filters = [
            'dari' => now()->startOfMonth()->toDateString(),
            'sampai' => now()->toDateString(),
        ];
        $reports = [
            'penjualan-tanggal',
            'penjualan-produk',
            'penjualan-pelanggan',
            'pembelian-tanggal',
            'pembelian-supplier',
            'stok-saat-ini',
            'mutasi-stok',
            'laba-rugi',
        ];

        foreach ($reports as $report) {
            $this->get(route('reports.show', ['report' => $report] + $filters))
                ->assertOk();
            $this->get(route('reports.excel', ['report' => $report] + $filters))
                ->assertOk()
                ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $pdf = $this->get(route('reports.pdf', ['report' => $report] + $filters))
                ->assertOk()
                ->assertHeader('content-type', 'application/pdf');
            $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        }

        $this->get(route('reports.show', ['report' => 'stok-saat-ini']))
            ->assertOk()
            ->assertSee('Total nilai persediaan')
            ->assertSee('Rp 1.000');
        $this->get(route('reports.show', ['report' => 'laba-rugi'] + $filters))
            ->assertSee('Rp 1.700')
            ->assertSee('Rp 1.000')
            ->assertSee('Rp 700');
        $this->get(route('reports.show', [
            'report' => 'mutasi-stok',
            ...$filters,
            'product_id' => $product->id,
        ]))->assertOk()->assertSee('Produk Laporan');
    }

    public function test_reports_require_the_laporan_permission(): void
    {
        $cashier = User::factory()->create();
        $cashier->assignRole('kasir');

        $this->actingAs($cashier)
            ->get(route('reports.index'))
            ->assertForbidden();
    }

    private function createSale(User $user, Product $product, int $total, string $number): Sale
    {
        $sale = Sale::query()->create([
            'nomor' => $number,
            'user_id' => $user->id,
            'tanggal' => now()->toDateString(),
            'subtotal' => $total,
            'diskon' => 0,
            'pajak' => 0,
            'total' => $total,
            'bayar' => $total,
            'kembalian' => 0,
            'metode_pembayaran' => Sale::PAYMENT_CASH,
            'status' => Sale::STATUS_COMPLETED,
        ]);
        $sale->items()->create([
            'product_id' => $product->id,
            'qty' => 1,
            'harga_jual' => $total,
            'diskon' => 0,
            'subtotal' => $total,
        ]);

        return $sale;
    }
}
