<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Piutang;
use App\Models\PiutangPayment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\PiutangService;
use App\Services\StoreSettingsService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PiutangManagementTest extends TestCase
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

    public function test_partial_payment_updates_piutang_legacy_receivable_and_sale_balances(): void
    {
        $customer = Customer::factory()->create();
        $sale = $this->createTempoSale($customer, 10000, 2000, now()->addDays(2)->toDateString());
        $piutang = $sale->piutang()->firstOrFail();

        $this->post(route('piutangs.payments.store', $piutang), [
            'jumlah' => 3000,
            'metode' => 'transfer',
            'catatan' => 'Cicilan pertama',
        ])->assertRedirect(route('piutangs.show', $piutang))
            ->assertSessionHas('success');

        $payment = PiutangPayment::query()->firstOrFail();
        $piutang->refresh();
        $sale->refresh();
        $this->assertSame('BYR-'.now()->format('Ymd').'-0001', $payment->nomor);
        $this->assertSame(5000, $piutang->terbayar);
        $this->assertSame(5000, $piutang->sisa);
        $this->assertSame(Piutang::STATUS_UNPAID, $piutang->status);
        $this->assertSame(5000, $sale->total_dibayar);
        $this->assertSame(5000, $sale->sisa);
        $this->assertSame('belum_lunas', $sale->status_pembayaran);
        $this->assertDatabaseHas('receivables', [
            'sale_id' => $sale->id,
            'terbayar' => 5000,
            'sisa' => 5000,
            'status' => 'sebagian',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'aksi' => 'piutang.dibuat',
            'subject_type' => Piutang::class,
            'subject_id' => $piutang->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'aksi' => 'piutang.pembayaran_dicatat',
            'subject_type' => PiutangPayment::class,
            'subject_id' => $payment->id,
        ]);
        $this->get(route('piutangs.payments.pdf', [$piutang, $payment]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertSee('%PDF-', false);

        $sale->refresh()->load(['customer', 'items.product', 'piutang']);
        $store = app(StoreSettingsService::class)->forDocument();
        $isCancelled = false;
        $invoiceHtml = view('pdf.sales.invoice', compact('sale', 'store', 'isCancelled'))->render();
        $receiptHtml = view('pdf.sales.receipt', compact('sale', 'store', 'isCancelled'))->render();
        $this->assertStringContainsString('DP / Uang Muka', $invoiceHtml);
        $this->assertStringContainsString('BELUM LUNAS', $invoiceHtml);
        $this->assertStringContainsString('Sisa hutang', $receiptHtml);
        $this->assertStringContainsString($sale->jatuh_tempo->format('d/m/Y'), $receiptHtml);
        $this->assertSame(5000, app(PiutangService::class)->totalHutangPelanggan($customer));
        $this->get(route('piutangs.show', $piutang))
            ->assertOk()
            ->assertSee('Cicilan pertama')
            ->assertSee('Segera jatuh tempo');
        $this->get(route('piutangs.index', [
            'customer_id' => $customer->id,
            'status' => Piutang::STATUS_UNPAID,
            'jatuh_tempo' => $piutang->jatuh_tempo->toDateString(),
        ]))
            ->assertOk()
            ->assertSee($sale->nomor)
            ->assertSee('Segera jatuh tempo');
        $this->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('Total hutang aktif')
            ->assertSee('Rp 5.000');
    }

    public function test_full_payment_marks_piutang_and_sale_as_paid(): void
    {
        $customer = Customer::factory()->create();
        $sale = $this->createTempoSale($customer, 12000);
        $piutang = $sale->piutang()->firstOrFail();

        $this->post(route('piutangs.payments.store', $piutang), [
            'jumlah' => 12000,
            'metode' => 'tunai',
        ])->assertRedirect(route('piutangs.show', $piutang));

        $this->assertSame(Piutang::STATUS_PAID, $piutang->fresh()->status);
        $this->assertSame(0, $piutang->fresh()->sisa);
        $this->assertSame('lunas', $sale->fresh()->status_pembayaran);
        $this->assertSame(0, $sale->fresh()->sisa);
        $this->assertSame(0, app(PiutangService::class)->totalHutangPelanggan($customer));
    }

    public function test_payment_exceeding_remaining_balance_is_rejected_without_changes(): void
    {
        $customer = Customer::factory()->create();
        $sale = $this->createTempoSale($customer, 9000, 1000);
        $piutang = $sale->piutang()->firstOrFail();

        $this->from(route('piutangs.show', $piutang))
            ->post(route('piutangs.payments.store', $piutang), [
                'jumlah' => 8001,
                'metode' => 'tunai',
            ])
            ->assertRedirect(route('piutangs.show', $piutang))
            ->assertSessionHasErrors('jumlah');

        $this->assertDatabaseCount('piutang_payments', 0);
        $this->assertSame(8000, $piutang->fresh()->sisa);
        $this->assertSame(1000, $sale->fresh()->total_dibayar);
    }

    public function test_customer_credit_limit_rejects_new_debt_that_exceeds_the_limit(): void
    {
        $customer = Customer::factory()->create(['batas_hutang' => 5000]);
        $this->createTempoSale($customer, 4000);
        $product = Product::factory()->create(['harga_jual' => 2000, 'stok' => 5]);

        $this->from(route('sales.create'))
            ->post(route('sales.store'), [
                'customer_id' => $customer->id,
                'tanggal' => now()->toDateString(),
                'jatuh_tempo' => now()->addDays(7)->toDateString(),
                'diskon' => 0,
                'pajak' => 0,
                'bayar' => 0,
                'metode_pembayaran' => 'tempo',
                'items' => [['product_id' => $product->id, 'qty' => 1]],
            ])
            ->assertRedirect(route('sales.create'))
            ->assertSessionHasErrors('customer_id');

        $this->assertDatabaseCount('sales', 1);
        $this->assertSame(5, $product->fresh()->stok);
        $this->assertStringContainsString('Batas hutang pelanggan terlampaui', implode(' ', session('errors')->get('customer_id')));
    }

    public function test_customer_search_returns_current_debt_limit_and_overdue_summary(): void
    {
        $customer = Customer::factory()->create(['batas_hutang' => 10000]);
        $sale = $this->createTempoSale($customer, 6000, 1000, now()->addDays(2)->toDateString());
        $sale->piutang()->firstOrFail()->update(['jatuh_tempo' => today()->subDay()]);

        $this->getJson(route('customers.search', ['q' => $customer->nama]))
            ->assertOk()
            ->assertJsonPath('data.0.total_hutang', 5000)
            ->assertJsonPath('data.0.batas_hutang', 10000)
            ->assertJsonPath('data.0.sisa_batas_hutang', 5000)
            ->assertJsonPath('data.0.ada_piutang_terlambat', true);
    }

    public function test_tempo_sale_creates_piutang_for_balance_and_deducts_stock(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['harga_jual' => 10000, 'stok' => 5]);

        $this->post(route('sales.store'), [
            'customer_id' => $customer->id,
            'tanggal' => now()->toDateString(),
            'jatuh_tempo' => now()->addDays(14)->toDateString(),
            'diskon' => 0,
            'pajak' => 0,
            'bayar' => 2500,
            'metode_pembayaran' => 'tempo',
            'items' => [['product_id' => $product->id, 'qty' => 2]],
        ])->assertRedirect();

        $sale = Sale::query()->firstOrFail();
        $piutang = $sale->piutang()->firstOrFail();
        $this->assertSame(20000, $sale->total);
        $this->assertSame(2500, $sale->total_dibayar);
        $this->assertSame(17500, $sale->sisa);
        $this->assertSame(17500, $piutang->sisa);
        $this->assertSame(3, $product->fresh()->stok);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'referensi_type' => Sale::class,
            'referensi_id' => $sale->id,
            'tipe' => 'keluar',
        ]);
    }

    public function test_full_down_payment_marks_tempo_sale_paid_without_creating_piutang(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['harga_jual' => 10000, 'stok' => 2]);

        $this->post(route('sales.store'), [
            'customer_id' => $customer->id,
            'tanggal' => now()->toDateString(),
            'jatuh_tempo' => now()->addDays(14)->toDateString(),
            'diskon' => 0,
            'pajak' => 0,
            'bayar' => 10000,
            'metode_pembayaran' => 'tempo',
            'items' => [['product_id' => $product->id, 'qty' => 1]],
        ])->assertRedirect();

        $sale = Sale::query()->firstOrFail();
        $this->assertSame('lunas', $sale->status_pembayaran);
        $this->assertSame(0, $sale->sisa);
        $this->assertNull($sale->jatuh_tempo);
        $this->assertDatabaseCount('piutangs', 0);
        $this->assertDatabaseCount('receivables', 0);
    }

    public function test_tempo_sale_rolls_back_debt_sale_if_a_product_has_insufficient_stock(): void
    {
        $customer = Customer::factory()->create();
        $first = Product::factory()->create(['nama' => 'Produk Aman', 'harga_jual' => 1000, 'stok' => 5]);
        $second = Product::factory()->create(['nama' => 'Produk Kurang', 'harga_jual' => 1000, 'stok' => 1]);

        $this->from(route('sales.create'))->post(route('sales.store'), [
            'customer_id' => $customer->id,
            'tanggal' => now()->toDateString(),
            'jatuh_tempo' => now()->addDays(14)->toDateString(),
            'diskon' => 0,
            'pajak' => 0,
            'bayar' => 0,
            'metode_pembayaran' => 'tempo',
            'items' => [
                ['product_id' => $first->id, 'qty' => 2],
                ['product_id' => $second->id, 'qty' => 2],
            ],
        ])->assertRedirect(route('sales.create'))->assertSessionHasErrors('items');

        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_items', 0);
        $this->assertDatabaseCount('piutangs', 0);
        $this->assertDatabaseCount('receivables', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertSame(5, $first->fresh()->stok);
        $this->assertSame(1, $second->fresh()->stok);
    }

    public function test_sale_cannot_be_cancelled_after_a_piutang_payment_is_recorded(): void
    {
        $customer = Customer::factory()->create();
        $sale = $this->createTempoSale($customer, 7000);
        $piutang = $sale->piutang()->firstOrFail();
        $product = Product::query()->findOrFail($sale->items()->value('product_id'));
        $movementCount = StockMovement::query()->count();

        $this->post(route('piutangs.payments.store', $piutang), [
            'jumlah' => 1000,
            'metode' => 'tunai',
        ])->assertRedirect(route('piutangs.show', $piutang));

        $this->from(route('sales.show', $sale))
            ->patch(route('sales.cancel', $sale))
            ->assertRedirect(route('sales.show', $sale))
            ->assertSessionHasErrors('sale');

        $this->assertSame(Sale::STATUS_COMPLETED, $sale->fresh()->status);
        $this->assertSame(Piutang::STATUS_UNPAID, $piutang->fresh()->status);
        $this->assertSame(6000, $piutang->fresh()->sisa);
        $this->assertSame(19, $product->fresh()->stok);
        $this->assertSame($movementCount, StockMovement::query()->count());
    }

    public function test_overdue_command_logs_each_unpaid_piutang_once_and_sidebar_shows_badge(): void
    {
        $customer = Customer::factory()->create();
        $sale = $this->createTempoSale($customer, 7000);
        $piutang = $sale->piutang()->firstOrFail();
        $piutang->update(['jatuh_tempo' => today()->subDay()]);
        $sale->update(['jatuh_tempo' => today()->subDay()]);

        $this->get(route('piutangs.index'))
            ->assertOk()
            ->assertSee('1 piutang jatuh tempo');

        $this->artisan('piutang:cek-jatuh-tempo')
            ->expectsOutput('1 log piutang jatuh tempo dibuat.')
            ->assertExitCode(0);
        $this->artisan('piutang:cek-jatuh-tempo')
            ->expectsOutput('0 log piutang jatuh tempo dibuat.')
            ->assertExitCode(0);

        $this->assertSame(1, ActivityLog::query()
            ->where('aksi', 'piutang.jatuh_tempo')
            ->where('subject_type', Piutang::class)
            ->where('subject_id', $piutang->id)
            ->count());
    }

    public function test_owner_can_view_piutang_but_cannot_record_payments(): void
    {
        $customer = Customer::factory()->create();
        $sale = $this->createTempoSale($customer, 5000);
        $piutang = $sale->piutang()->firstOrFail();
        $owner = User::factory()->create();
        $owner->assignRole('owner');

        $this->actingAs($owner)
            ->get(route('piutangs.index'))
            ->assertOk()
            ->assertSee('Piutang Pelanggan')
            ->assertSee($sale->nomor);
        $this->post(route('piutangs.payments.store', $piutang), [
            'jumlah' => 5000,
            'metode' => 'tunai',
        ])->assertForbidden();
    }

    public function test_cancelling_a_sale_cancels_its_piutang_and_excludes_it_from_customer_debt(): void
    {
        $customer = Customer::factory()->create();
        $sale = $this->createTempoSale($customer, 7000);
        $piutang = $sale->piutang()->firstOrFail();

        $this->patch(route('sales.cancel', $sale))->assertRedirect(route('sales.show', $sale));

        $this->assertSame(Piutang::STATUS_CANCELLED, $piutang->fresh()->status);
        $this->assertSame('lunas', $sale->fresh()->status_pembayaran);
        $this->assertSame(0, $sale->fresh()->sisa);
        $this->assertSame(0, app(PiutangService::class)->totalHutangPelanggan($customer));
        $this->assertDatabaseHas('receivables', [
            'sale_id' => $sale->id,
            'status' => 'batal',
            'sisa' => 0,
        ]);
    }

    private function createTempoSale(
        Customer $customer,
        int $amount,
        int $downPayment = 0,
        ?string $dueDate = null,
    ): Sale {
        $product = Product::factory()->create([
            'harga_jual' => $amount,
            'stok' => 20,
        ]);

        $this->post(route('sales.store'), [
            'customer_id' => $customer->id,
            'tanggal' => now()->toDateString(),
            'jatuh_tempo' => $dueDate ?? now()->addDays(14)->toDateString(),
            'diskon' => 0,
            'pajak' => 0,
            'bayar' => $downPayment,
            'metode_pembayaran' => 'tempo',
            'items' => [['product_id' => $product->id, 'qty' => 1]],
        ])->assertRedirect()->assertSessionHas('success');

        return Sale::query()->latest('id')->firstOrFail();
    }
}
