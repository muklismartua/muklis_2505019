<?php

namespace Tests\Feature;

use App\Models\HutangSupplier;
use App\Models\HutangSupplierPayment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use App\Services\HutangSupplierService;
use App\Services\PembelianService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class SupplierDebtManagementTest extends TestCase
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

    public function test_credit_purchase_creates_supplier_debt_and_partial_then_full_payments_sync_balances(): void
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create(['stok' => 1, 'harga_beli' => 5000]);

        $this->post(route('purchases.store'), $this->creditPurchaseData($supplier, $product, [
            'bayar' => 2000,
        ]))->assertRedirect()->assertSessionHas('success');

        $purchase = Purchase::query()->with('hutangSupplier')->firstOrFail();
        $hutang = $purchase->hutangSupplier;
        $this->assertNotNull($hutang);
        $this->assertSame(10000, $purchase->total);
        $this->assertSame(2000, $purchase->total_dibayar);
        $this->assertSame(8000, $purchase->sisa);
        $this->assertSame(8000, $hutang->sisa);
        $this->assertSame('belum_lunas', $purchase->status_pembayaran);
        $this->assertSame('belum_lunas', $hutang->status);
        $this->assertSame(3, $product->fresh()->stok);
        $this->assertDatabaseHas('activity_logs', [
            'aksi' => 'hutang_supplier.dibuat',
            'subject_type' => HutangSupplier::class,
            'subject_id' => $hutang->id,
        ]);

        $this->post(route('supplier-debts.payments.store', $hutang), [
            'jumlah' => 3000,
            'metode' => 'transfer',
            'catatan' => 'Cicilan pertama',
        ])->assertRedirect(route('supplier-debts.show', $hutang))->assertSessionHas('success');

        $payment = HutangSupplierPayment::query()->where('jumlah', 3000)->firstOrFail();
        $this->assertSame('BYH-'.now()->format('Ymd').'-0002', $payment->nomor);
        $this->assertDatabaseHas('hutang_supplier_payments', [
            'hutang_supplier_id' => $hutang->id,
            'jumlah' => 2000,
            'metode' => 'tunai',
            'catatan' => "Uang muka pembelian {$purchase->nomor}",
        ]);
        $this->assertSame(5000, $hutang->fresh()->sisa);
        $this->assertSame(5000, $purchase->fresh()->total_dibayar);
        $this->assertSame(5000, $purchase->fresh()->sisa);

        $this->post(route('supplier-debts.payments.store', $hutang), [
            'jumlah' => 5000,
            'metode' => 'tunai',
        ])->assertRedirect(route('supplier-debts.show', $hutang));

        $this->assertSame(HutangSupplier::STATUS_PAID, $hutang->fresh()->status);
        $this->assertSame(0, $hutang->fresh()->sisa);
        $this->assertSame('lunas', $purchase->fresh()->status_pembayaran);
        $this->assertSame(0, $purchase->fresh()->sisa);
        $this->assertDatabaseHas('activity_logs', [
            'aksi' => 'hutang_supplier.pembayaran_dicatat',
            'subject_type' => HutangSupplierPayment::class,
            'subject_id' => $payment->id,
        ]);

        $this->get(route('supplier-debts.show', $hutang))
            ->assertOk()
            ->assertSee('Cicilan pertama')
            ->assertSee('Rp 10.000');
        $this->get(route('supplier-debts.index', [
            'supplier_id' => $supplier->id,
            'status' => 'lunas',
        ]))->assertOk()->assertSee($purchase->nomor);
    }

    public function test_credit_purchase_requires_due_date_rejects_excessive_down_payment_and_full_dp_creates_no_debt(): void
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create(['stok' => 0]);

        $this->from(route('purchases.create'))
            ->post(route('purchases.store'), $this->creditPurchaseData($supplier, $product, [
                'jatuh_tempo' => '',
            ]))
            ->assertRedirect(route('purchases.create'))
            ->assertSessionHasErrors('jatuh_tempo');
        $this->from(route('purchases.create'))
            ->post(route('purchases.store'), $this->creditPurchaseData($supplier, $product, [
                'bayar' => 10001,
            ]))
            ->assertRedirect(route('purchases.create'))
            ->assertSessionHasErrors('bayar');

        $this->assertDatabaseCount('purchases', 0);
        $this->assertSame(0, $product->fresh()->stok);

        $this->post(route('purchases.store'), $this->creditPurchaseData($supplier, $product, [
            'bayar' => 10000,
        ]))->assertRedirect();

        $purchase = Purchase::query()->firstOrFail();
        $this->assertSame('lunas', $purchase->status_pembayaran);
        $this->assertSame(10000, $purchase->total_dibayar);
        $this->assertSame(0, $purchase->sisa);
        $this->assertDatabaseCount('hutang_suppliers', 0);
    }

    public function test_overpayment_and_cancellation_after_down_payment_or_installment_are_rejected(): void
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create(['stok' => 0]);
        $this->post(route('purchases.store'), $this->creditPurchaseData($supplier, $product, [
            'bayar' => 1000,
        ]))->assertRedirect();

        $purchase = Purchase::query()->firstOrFail();
        $hutang = $purchase->hutangSupplier()->firstOrFail();
        $this->from(route('supplier-debts.show', $hutang))
            ->post(route('supplier-debts.payments.store', $hutang), [
                'jumlah' => $hutang->sisa + 1,
                'metode' => 'tunai',
            ])
            ->assertRedirect(route('supplier-debts.show', $hutang))
            ->assertSessionHasErrors('jumlah');

        $this->patch(route('purchases.cancel', $purchase))
            ->assertRedirect()
            ->assertSessionHasErrors('purchase');
        $this->assertSame(Purchase::STATUS_COMPLETED, $purchase->fresh()->status);
        $this->assertSame(2, $product->fresh()->stok);
        $this->assertSame(HutangSupplier::STATUS_UNPAID, $hutang->fresh()->status);
        $this->assertDatabaseHas('hutang_supplier_payments', [
            'hutang_supplier_id' => $hutang->id,
            'jumlah' => 1000,
        ]);
    }

    public function test_credit_purchase_rolls_back_purchase_items_stock_and_debt_if_debt_creation_fails(): void
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create(['stok' => 4]);
        $service = Mockery::mock(HutangSupplierService::class);
        $service->shouldReceive('buatDariPembelian')
            ->once()
            ->andThrow(new RuntimeException('Simulasi kegagalan hutang supplier.'));
        $this->app->instance(HutangSupplierService::class, $service);

        try {
            app(PembelianService::class)->simpan(
                $this->creditPurchaseData($supplier, $product, ['bayar' => 0]),
                $this->admin,
            );
            $this->fail('Expected the simulated debt failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulasi kegagalan hutang supplier.', $exception->getMessage());
        }

        $this->assertDatabaseCount('purchases', 0);
        $this->assertDatabaseCount('purchase_items', 0);
        $this->assertDatabaseCount('hutang_suppliers', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertSame(4, $product->fresh()->stok);
    }

    public function test_owner_can_view_supplier_debt_summary_and_export_report(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('owner');
        $this->actingAs($owner);

        $supplier = Supplier::factory()->create(['nama' => 'Supplier Terlambat']);
        $product = Product::factory()->create(['stok' => 0]);
        $purchase = app(PembelianService::class)->simpan(
            $this->creditPurchaseData($supplier, $product, [
                'bayar' => 0,
            ]),
            $owner,
        );
        $purchase->hutangSupplier()->update(['jatuh_tempo' => today()->subDay()->toDateString()]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Total Hutang Supplier Aktif')
            ->assertSee('Hutang Supplier Jatuh Tempo')
            ->assertSee('Rp 10.000');

        $this->get(route('reports.show', [
            'report' => 'hutang-supplier',
            'supplier_id' => $supplier->id,
            'status' => 'belum_lunas',
            'jatuh_dari' => today()->subDays(2)->toDateString(),
            'jatuh_sampai' => today()->toDateString(),
        ]))->assertOk()->assertSee($purchase->nomor);
        $this->get(route('reports.excel', ['report' => 'hutang-supplier']))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $pdf = $this->get(route('reports.pdf', ['report' => 'hutang-supplier']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
    }

    public function test_owner_can_view_but_cannot_record_supplier_debt_payments(): void
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();
        $purchase = app(PembelianService::class)->simpan(
            $this->creditPurchaseData($supplier, $product),
            $this->admin,
        );
        $owner = User::factory()->create();
        $owner->assignRole('owner');
        $this->actingAs($owner);

        $this->get(route('supplier-debts.index'))->assertOk();
        $this->get(route('supplier-debts.show', $purchase->hutangSupplier))->assertOk();
        $this->post(route('supplier-debts.payments.store', $purchase->hutangSupplier), [
            'jumlah' => 100,
            'metode' => 'tunai',
        ])->assertForbidden();
    }

    private function creditPurchaseData(Supplier $supplier, Product $product, array $overrides = []): array
    {
        return [
            'supplier_id' => $supplier->id,
            'tanggal' => today()->toDateString(),
            'diskon' => 0,
            'metode_pembayaran' => 'tempo',
            'bayar' => 0,
            'metode_dp' => 'tunai',
            'jatuh_tempo' => today()->addDays(14)->toDateString(),
            'items' => [['product_id' => $product->id, 'qty' => 2, 'harga_beli' => 5000]],
            ...$overrides,
        ];
    }
}
