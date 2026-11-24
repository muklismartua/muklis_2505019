<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerQuickCreateTest extends TestCase
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

    public function test_customer_search_matches_name_or_phone_and_excludes_inactive_customers(): void
    {
        $customer = Customer::factory()->create([
            'nama' => 'Dewi Anggraini',
            'telepon' => '08123456789',
        ]);
        Customer::factory()->create([
            'nama' => 'Tidak Aktif',
            'telepon' => '08120000000',
            'aktif' => false,
        ]);

        $this->getJson(route('customers.search', ['q' => 'Dewi']))
            ->assertOk()
            ->assertJsonPath('data.0.id', $customer->id)
            ->assertJsonPath('data.0.kode', $customer->kode)
            ->assertJsonPath('data.0.batas_hutang', 0)
            ->assertJsonPath('data.0.total_hutang', 0)
            ->assertJsonPath('data.0.sisa_batas_hutang', null)
            ->assertJsonPath('data.0.ada_piutang_terlambat', false);
        $this->getJson(route('customers.search', ['q' => '123456789']))
            ->assertOk()
            ->assertJsonPath('data.0.id', $customer->id);
        $this->getJson(route('customers.search', ['q' => 'Tidak Aktif']))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_quick_create_returns_customer_with_automatic_code_and_defaults(): void
    {
        $this->get(route('sales.create'))
            ->assertOk()
            ->assertSee('customer-search')
            ->assertSee('+ Pelanggan Baru')
            ->assertSee('Tambah pelanggan baru');

        $response = $this->postJson(route('customers.quick-store'), [
            'nama' => 'Pelanggan Baru',
            'telepon' => '08123456789',
            'alamat' => 'Jakarta',
        ])->assertCreated()
            ->assertJsonPath('customer.kode', 'PLG-0001')
            ->assertJsonPath('customer.nama', 'Pelanggan Baru')
            ->assertJsonPath('customer.telepon', '08123456789');

        $this->assertDatabaseHas('customers', [
            'id' => $response->json('customer.id'),
            'kode' => 'PLG-0001',
            'batas_hutang' => 0,
            'aktif' => true,
        ]);
    }

    public function test_duplicate_quick_create_offers_the_existing_customer(): void
    {
        $customer = Customer::factory()->create([
            'nama' => 'Budi Santoso',
            'telepon' => '0812-3456',
        ]);

        $this->postJson(route('customers.quick-store'), [
            'nama' => 'Budi Santoso',
            'telepon' => '0812 3456',
        ])->assertStatus(409)
            ->assertJsonPath('duplicate', true)
            ->assertJsonPath('customer.id', $customer->id);

        $this->assertDatabaseCount('customers', 1);
    }

    public function test_cashier_can_quick_create_but_only_admin_can_delete_customers(): void
    {
        $cashier = User::factory()->create();
        $cashier->assignRole('kasir');
        $customer = Customer::factory()->create();

        $this->actingAs($cashier)
            ->postJson(route('customers.quick-store'), [
                'nama' => 'Pelanggan Kasir',
                'telepon' => '081299998888',
            ])->assertCreated();

        $this->delete(route('customers.destroy', $customer))->assertForbidden();
        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
    }

    public function test_full_customer_crud_saves_and_displays_credit_limit_and_status(): void
    {
        $this->get(route('customers.create'))
            ->assertOk()
            ->assertSee('Batas hutang (Rp; 0 = tanpa batas)');

        $this->post(route('customers.store'), [
            'nama' => 'Pelanggan Berbatas',
            'batas_hutang' => 250000,
            'aktif' => 0,
        ])->assertRedirect(route('customers.index'));

        $customer = Customer::query()->where('nama', 'Pelanggan Berbatas')->firstOrFail();
        $this->assertSame(250000, $customer->batas_hutang);
        $this->assertFalse($customer->aktif);
        $this->get(route('customers.index'))
            ->assertOk()
            ->assertSee('Rp 250.000')
            ->assertSee('Nonaktif');

        $this->put(route('customers.update', $customer), [
            'nama' => 'Pelanggan Berbatas',
            'batas_hutang' => 0,
            'aktif' => 1,
        ])->assertRedirect(route('customers.index'));

        $this->assertSame(0, $customer->fresh()->batas_hutang);
        $this->assertTrue($customer->fresh()->aktif);
    }
}
