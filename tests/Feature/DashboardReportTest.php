<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Piutang;
use App\Models\PiutangPayment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\PiutangService;
use App\Services\ReportService;
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
        $this->assertSame(1000, $data['stats']['cash_received_today']);
        $this->assertSame(1000, $data['stats']['cash_received_this_month']);
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

    public function test_dashboard_shows_receivables_and_separates_sales_from_cash_received(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('owner');
        $firstCustomer = Customer::factory()->create(['nama' => 'Pelanggan Hutang A']);
        $secondCustomer = Customer::factory()->create(['nama' => 'Pelanggan Hutang B']);
        $firstPiutang = $this->createCreditSale(
            $owner,
            $firstCustomer,
            total: 10000,
            downPayment: 2000,
            dueDate: today()->subDays(10)->toDateString(),
            number: 'PJ-'.now()->format('Ymd').'-0301',
        );
        $this->createCreditSale(
            $owner,
            $secondCustomer,
            total: 5000,
            downPayment: 0,
            dueDate: today()->subDays(2)->toDateString(),
            number: 'PJ-'.now()->format('Ymd').'-0302',
        );

        $this->actingAs($owner);
        app(PiutangService::class)->catatPembayaran($firstPiutang, 3000, 'tunai');

        $data = app(DashboardService::class)->data($owner, 7);
        $this->assertSame(15000, $data['stats']['sales_today']);
        $this->assertSame(5000, $data['stats']['cash_received_today']);
        $this->assertSame(10000, $data['stats']['active_receivables']);
        $this->assertSame(2, $data['stats']['overdue_receivable_count']);
        $this->assertSame(10000, $data['stats']['overdue_receivable_total']);
        $this->assertSame(
            ['Pelanggan Hutang A', 'Pelanggan Hutang B'],
            $data['top_debtors']->pluck('nama')->all(),
        );

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Total Piutang Aktif')
            ->assertSee('Piutang Jatuh Tempo')
            ->assertSee('Total diterima hari ini')
            ->assertSee('Pelanggan Hutang A');
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
            'piutang-pelanggan',
            'hutang-supplier',
            'piutang-aging',
            'riwayat-pembayaran-piutang',
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

    public function test_sales_reports_separate_cash_received_and_receivable_reports_cover_balance_aging_and_payments(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('owner');
        $customer = Customer::factory()->create(['nama' => 'Pelanggan Laporan Piutang']);
        $dueDate = today()->subDays(45)->toDateString();
        $piutang = $this->createCreditSale(
            $owner,
            $customer,
            10000,
            2000,
            $dueDate,
            'PJ-'.now()->format('Ymd').'-0401',
        );
        $payment = PiutangPayment::query()->create([
            'piutang_id' => $piutang->id,
            'nomor' => 'BYR-'.now()->format('Ymd').'-0001',
            'tanggal' => today()->toDateString(),
            'jumlah' => 1000,
            'metode' => 'transfer',
            'user_id' => $owner->id,
        ]);
        $piutang->update(['terbayar' => 3000, 'sisa' => 7000]);
        $piutang->sale()->update(['total_dibayar' => 3000, 'sisa' => 7000]);
        $product = Product::factory()->create(['sku' => 'PIUTANG-001', 'nama' => 'Produk Piutang']);
        SaleItem::query()->create([
            'sale_id' => $piutang->sale_id,
            'product_id' => $product->id,
            'qty' => 1,
            'harga_jual' => 10000,
            'diskon' => 0,
            'subtotal' => 10000,
        ]);

        $filters = app(ReportService::class)->normalizeFilters([
            'dari' => today()->toDateString(),
            'sampai' => today()->toDateString(),
            'customer_id' => $customer->id,
            'status' => Piutang::STATUS_UNPAID,
            'jatuh_dari' => $dueDate,
            'jatuh_sampai' => $dueDate,
        ]);
        $reportService = app(ReportService::class);
        $salesByDate = $reportService->rows('penjualan-tanggal', $filters, paginate: false)->first();
        $this->assertSame(10000, $salesByDate['penjualan']);
        $this->assertSame(3000, $salesByDate['diterima']);

        $salesByCustomer = $reportService->rows('penjualan-pelanggan', $filters, paginate: false)->first();
        $this->assertSame(10000, $salesByCustomer['penjualan']);
        $this->assertSame(3000, $salesByCustomer['diterima']);
        $salesByProduct = $reportService->rows('penjualan-produk', [
            ...$filters,
            'product_id' => $product->id,
        ], paginate: false)->first();
        $this->assertSame(10000, $salesByProduct['penjualan']);
        $this->assertSame(3000, $salesByProduct['diterima']);

        $receivableRows = $reportService->rows('piutang-pelanggan', $filters, paginate: false);
        $this->assertCount(1, $receivableRows);
        $this->assertSame(7000, $receivableRows->first()['sisa']);
        $agingRows = $reportService->rows(
            'piutang-aging',
            ['customer_id' => $customer->id],
            paginate: false,
        );
        $this->assertSame(7000, $agingRows->first()['hari_31_60']);
        $paymentRows = $reportService->rows('riwayat-pembayaran-piutang', [
            'dari' => today()->toDateString(),
            'sampai' => today()->toDateString(),
            'customer_id' => $customer->id,
        ], paginate: false);
        $this->assertSame($payment->nomor, $paymentRows->first()['nomor_pembayaran']);
        $this->assertSame(1000, $paymentRows->first()['jumlah']);

        $this->actingAs($owner)
            ->get(route('reports.show', ['report' => 'piutang-aging', 'customer_id' => $customer->id]))
            ->assertOk()
            ->assertSee('31–60 Hari')
            ->assertSee('Rp 7.000');
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

    private function createCreditSale(
        User $user,
        Customer $customer,
        int $total,
        int $downPayment,
        string $dueDate,
        string $number,
    ): Piutang {
        $sale = Sale::query()->create([
            'nomor' => $number,
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'tanggal' => now()->toDateString(),
            'subtotal' => $total,
            'diskon' => 0,
            'pajak' => 0,
            'total' => $total,
            'bayar' => $downPayment,
            'kembalian' => 0,
            'metode_pembayaran' => Sale::PAYMENT_TEMPO,
            'status' => Sale::STATUS_COMPLETED,
            'status_pembayaran' => 'belum_lunas',
            'jatuh_tempo' => $dueDate,
            'total_dibayar' => $downPayment,
            'sisa' => $total - $downPayment,
        ]);

        return Piutang::query()->create([
            'sale_id' => $sale->id,
            'customer_id' => $customer->id,
            'total' => $total,
            'terbayar' => $downPayment,
            'sisa' => $total - $downPayment,
            'jatuh_tempo' => $dueDate,
            'status' => Piutang::STATUS_UNPAID,
        ]);
    }
}
