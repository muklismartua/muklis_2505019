<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MasterDataCrudTest extends TestCase
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

    public function test_lookup_resources_support_create_update_search_and_delete(): void
    {
        $this->get(route('categories.create'))->assertOk();
        $this->post(route('categories.store'), ['nama' => 'Minuman'])
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('success');
        $category = Category::query()->where('nama', 'Minuman')->firstOrFail();
        $this->get(route('categories.show', $category))->assertOk()->assertSee('Minuman');
        $this->get(route('categories.edit', $category))->assertOk();
        $this->put(route('categories.update', $category), ['nama' => 'Minuman Segar'])
            ->assertRedirect(route('categories.index'));
        $this->get(route('categories.index', ['q' => 'Segar']))->assertOk()->assertSee('Minuman Segar');
        $this->delete(route('categories.destroy', $category))->assertRedirect(route('categories.index'));
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);

        $this->post(route('units.store'), ['nama' => 'Lusin', 'singkatan' => 'lsn'])->assertRedirect(route('units.index'));
        $unit = Unit::query()->where('nama', 'Lusin')->firstOrFail();
        $this->put(route('units.update', $unit), ['nama' => 'Lusin Besar', 'singkatan' => 'lbg'])->assertRedirect(route('units.index'));
        $this->assertDatabaseHas('units', ['id' => $unit->id, 'singkatan' => 'lbg']);

        $this->post(route('suppliers.store'), [
            'nama' => 'Supplier Baru',
            'telepon' => '08123456789',
            'email' => 'supplier@example.test',
            'alamat' => 'Jakarta',
        ])->assertRedirect(route('suppliers.index'));
        $supplier = Supplier::query()->where('nama', 'Supplier Baru')->firstOrFail();
        $this->put(route('suppliers.update', $supplier), [
            'nama' => 'Supplier Update',
            'telepon' => '08123456789',
            'email' => 'supplier@example.test',
            'alamat' => 'Bandung',
        ])->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'nama' => 'Supplier Update']);

        $this->post(route('customers.store'), ['nama' => 'Pelanggan Baru'])
            ->assertRedirect(route('customers.index'));
        $customer = Customer::query()->where('nama', 'Pelanggan Baru')->firstOrFail();
        $this->put(route('customers.update', $customer), ['nama' => 'Pelanggan Update'])
            ->assertRedirect(route('customers.index'));
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'nama' => 'Pelanggan Update']);
        $this->get(route('units.index'))->assertOk();
        $this->get(route('suppliers.index'))->assertOk();
        $this->get(route('customers.index'))->assertOk();
    }

    public function test_lookup_deletion_is_rejected_when_records_are_used(): void
    {
        $category = Category::factory()->create();
        $unit = Unit::factory()->create();
        $supplier = Supplier::factory()->create();
        $customer = Customer::factory()->create();
        Product::factory()->create(['category_id' => $category->id, 'unit_id' => $unit->id]);
        InventoryTransaction::query()->create([
            'transaction_number' => 'BUY-USED-SUPPLIER',
            'type' => InventoryTransaction::TYPE_PURCHASE,
            'transaction_date' => now()->toDateString(),
            'total' => 0,
            'supplier_id' => $supplier->id,
        ]);
        InventoryTransaction::query()->create([
            'transaction_number' => 'SALE-USED-CUSTOMER',
            'type' => InventoryTransaction::TYPE_SALE,
            'transaction_date' => now()->toDateString(),
            'total' => 0,
            'customer_id' => $customer->id,
        ]);

        $this->delete(route('categories.destroy', $category))->assertSessionHasErrors('record');
        $this->delete(route('units.destroy', $unit))->assertSessionHasErrors('record');
        $this->delete(route('suppliers.destroy', $supplier))->assertSessionHasErrors('record');
        $this->delete(route('customers.destroy', $customer))->assertSessionHasErrors('record');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
        $this->assertDatabaseHas('units', ['id' => $unit->id]);
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
    }

    public function test_product_crud_filters_and_never_accepts_manual_stock_changes(): void
    {
        Storage::fake('public');
        $category = Category::factory()->create();
        $unit = Unit::factory()->create();
        $payload = [
            'sku' => 'PRD-001',
            'nama' => 'Teh Melati',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'harga_beli' => 7000,
            'harga_jual' => 10000,
            'stok_minimum' => 5,
            'aktif' => 1,
            'stok' => 900,
            'foto' => UploadedFile::fake()->image('teh.png'),
        ];

        $this->get(route('products.create'))->assertOk();
        $this->post(route('products.store'), $payload)
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('success');
        $product = Product::query()->where('sku', 'PRD-001')->firstOrFail();
        $this->get(route('products.show', $product))->assertOk()->assertSee('Teh Melati');
        $this->get(route('products.edit', $product))->assertOk();
        $this->assertSame(0, $product->stok);
        $this->assertNotNull($product->foto);
        Storage::disk('public')->assertExists($product->foto);

        $this->put(route('products.update', $product), [
            ...$payload,
            'nama' => 'Teh Melati Premium',
            'foto' => null,
            'stok' => 450,
        ])->assertRedirect(route('products.index'));
        $product->refresh();
        $this->assertSame('Teh Melati Premium', $product->nama);
        $this->assertSame(0, $product->stok);
        Storage::disk('public')->assertExists($product->foto);

        $this->get(route('products.index', ['category_id' => $category->id, 'status' => 'aktif']))
            ->assertOk()
            ->assertSee('Teh Melati Premium');
        $this->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('success');
        $this->assertSoftDeleted('products', ['id' => $product->id]);
        Storage::disk('public')->assertMissing($product->foto);
    }

    public function test_view_only_role_can_list_products_but_cannot_manage_them(): void
    {
        $cashier = User::factory()->create();
        $cashier->assignRole('kasir');
        $this->actingAs($cashier);

        $this->get(route('products.index'))->assertOk();
        $this->get(route('products.create'))->assertForbidden();
        $this->post(route('products.store'), [])->assertForbidden();
    }

    public function test_transactions_keep_the_selected_supplier_or_customer_reference(): void
    {
        $supplier = Supplier::factory()->create();
        $customer = Customer::factory()->create();
        $product = Product::factory()->create([
            'sku' => 'LINK-001',
            'harga_beli' => 1000,
            'harga_jual' => 1500,
        ]);

        $this->get(route('transactions.create'))
            ->assertOk()
            ->assertSee('LINK-001')
            ->assertSee($supplier->nama)
            ->assertSee($customer->nama);

        $this->post(route('transactions.store'), [
            'type' => InventoryTransaction::TYPE_PURCHASE,
            'transaction_date' => now()->toDateString(),
            'supplier_id' => $supplier->id,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertRedirect();
        $purchase = InventoryTransaction::query()->where('type', InventoryTransaction::TYPE_PURCHASE)->firstOrFail();
        $this->assertSame($supplier->id, $purchase->supplier_id);

        $this->post(route('transactions.store'), [
            'type' => InventoryTransaction::TYPE_SALE,
            'transaction_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertRedirect();
        $sale = InventoryTransaction::query()->where('type', InventoryTransaction::TYPE_SALE)->firstOrFail();
        $this->assertSame($customer->id, $sale->customer_id);
    }

    public function test_master_data_seeder_creates_and_reuses_the_requested_sample_records(): void
    {
        $this->seed(MasterDataSeeder::class);
        $this->seed(MasterDataSeeder::class);

        $this->assertDatabaseCount('categories', 3);
        $this->assertDatabaseCount('units', 3);
        $this->assertDatabaseCount('suppliers', 3);
        $this->assertDatabaseCount('customers', 3);
        $this->assertDatabaseCount('products', 10);
    }
}
