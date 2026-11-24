<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockManual;
use App\Models\StockMovement;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockManualManagementTest extends TestCase
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

    public function test_manual_addition_saves_multi_item_records_and_updates_stock(): void
    {
        $first = Product::factory()->create(['nama' => 'Teh Hijau', 'stok' => 4]);
        $second = Product::factory()->create(['nama' => 'Kopi Hitam', 'stok' => 0]);

        $this->get(route('stock-manuals.create', ['product_id' => $first->id]))
            ->assertOk()
            ->assertSee('Cari dan tambahkan produk')
            ->assertSee('Stok sekarang')
            ->assertSee('Tambah/Kurang Stok');

        $this->post(route('stock-manuals.store'), [
            'tipe' => 'tambah',
            'tanggal' => now()->toDateString(),
            'alasan' => 'stok_awal',
            'keterangan' => 'Pengisian stok awal',
            'items' => [
                ['product_id' => $first->id, 'qty' => 3],
                ['product_id' => $second->id, 'qty' => 8],
            ],
        ])->assertRedirect(route('stock-manuals.index'))->assertSessionHas('success');

        $manuals = StockManual::query()->orderBy('id')->get();
        $this->assertCount(2, $manuals);
        $this->assertSame('SM-'.now()->format('Ymd').'-0001', $manuals[0]->nomor);
        $this->assertSame('SM-'.now()->format('Ymd').'-0002', $manuals[1]->nomor);
        $this->assertSame(7, $first->fresh()->stok);
        $this->assertSame(8, $second->fresh()->stok);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $first->id,
            'tipe' => 'masuk',
            'qty' => 3,
            'referensi_type' => StockManual::class,
            'referensi_id' => $manuals[0]->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'aksi' => 'stok_manual.dibuat',
            'subject_type' => StockManual::class,
            'subject_id' => $manuals[0]->id,
        ]);
        $this->get(route('stock.show', $first))
            ->assertOk()
            ->assertSee($manuals[0]->nomor)
            ->assertSee('Stok awal: Pengisian stok awal');
    }

    public function test_manual_subtraction_updates_stock_and_history_filters_work(): void
    {
        $product = Product::factory()->create(['nama' => 'Susu UHT', 'stok' => 10, 'stok_minimum' => 10]);

        $this->post(route('stock-manuals.store'), [
            'tipe' => 'kurang',
            'tanggal' => now()->toDateString(),
            'alasan' => 'barang_rusak',
            'keterangan' => 'Kemasan bocor',
            'items' => [['product_id' => $product->id, 'qty' => 4]],
        ])->assertRedirect(route('stock-manuals.index'));

        $manual = StockManual::query()->firstOrFail();
        $this->assertSame(6, $product->fresh()->stok);
        $this->assertSame('keluar', $manual->stockMovement()->firstOrFail()->tipe);
        $this->assertDatabaseHas('stock_movements', [
            'referensi_type' => StockManual::class,
            'referensi_id' => $manual->id,
            'keterangan' => 'Barang rusak: Kemasan bocor',
        ]);

        $this->get(route('stock-manuals.index', [
            'dari' => now()->toDateString(),
            'sampai' => now()->toDateString(),
            'tipe' => 'kurang',
            'alasan' => 'barang_rusak',
            'product_id' => $product->id,
        ]))
            ->assertOk()
            ->assertSee($manual->nomor)
            ->assertSee('Kemasan bocor');
        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Tambah/Kurang Stok');
        $this->get(route('stock.low-stock'))
            ->assertOk()
            ->assertSee('Tambah/Kurang Stok');
    }

    public function test_manual_subtraction_cannot_exceed_available_stock(): void
    {
        $product = Product::factory()->create(['nama' => 'Barang Terbatas', 'stok' => 2]);

        $this->from(route('stock-manuals.create'))->post(route('stock-manuals.store'), [
            'tipe' => 'kurang',
            'tanggal' => now()->toDateString(),
            'alasan' => 'koreksi',
            'items' => [['product_id' => $product->id, 'qty' => 3]],
        ])
            ->assertSessionHasErrors('items');

        $this->assertStringContainsString('Barang Terbatas', implode(' ', session('errors')->get('items')));
        $this->assertSame(2, $product->fresh()->stok);
        $this->assertDatabaseCount('stock_manuals', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_failure_on_a_later_item_rolls_back_the_whole_manual_operation(): void
    {
        $first = Product::factory()->create(['nama' => 'Tambah Ini', 'stok' => 5]);
        $second = Product::factory()->create(['nama' => 'Kurang Ini', 'stok' => 1]);

        $this->from(route('stock-manuals.create'))->post(route('stock-manuals.store'), [
            'tipe' => 'kurang',
            'tanggal' => now()->toDateString(),
            'alasan' => 'koreksi',
            'items' => [
                ['product_id' => $first->id, 'qty' => 4],
                ['product_id' => $second->id, 'qty' => 2],
            ],
        ])
            ->assertSessionHasErrors('items');

        $this->assertSame(5, $first->fresh()->stok);
        $this->assertSame(1, $second->fresh()->stok);
        $this->assertDatabaseCount('stock_manuals', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseMissing('activity_logs', ['aksi' => 'stok_manual.dibuat']);
    }

    public function test_only_users_with_stock_management_permission_can_create_manual_changes(): void
    {
        $warehouseStaff = User::factory()->create();
        $warehouseStaff->assignRole('staf_gudang');
        $this->actingAs($warehouseStaff)
            ->get(route('stock-manuals.create'))
            ->assertOk()
            ->assertSee('Stok Manual');

        $cashier = User::factory()->create();
        $cashier->assignRole('kasir');
        $this->actingAs($cashier)->get(route('stock-manuals.create'))->assertForbidden();
        $this->post(route('stock-manuals.store'), [])->assertForbidden();
    }
}
