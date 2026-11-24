<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Piutang;
use App\Models\PiutangPayment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\PiutangService;
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
            'jatuh_tempo' => $dueDate,
            'diskon' => 0,
            'pajak' => 0,
            'bayar' => $downPayment,
            'metode_pembayaran' => 'tempo',
            'items' => [['product_id' => $product->id, 'qty' => 1]],
        ])->assertRedirect()->assertSessionHas('success');

        return Sale::query()->latest('id')->firstOrFail();
    }
}
