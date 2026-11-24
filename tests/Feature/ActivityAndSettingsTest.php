<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\User;
use App\Services\NomorTransaksiService;
use App\Services\StoreSettingsService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityAndSettingsTest extends TestCase
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

    public function test_master_data_observer_skips_stock_only_updates_and_logs_master_changes(): void
    {
        $product = Product::factory()->create(['nama' => 'Produk Awal', 'stok' => 4]);
        $this->assertSame(1, ActivityLog::query()->where('subject_type', Product::class)->where('subject_id', $product->id)->count());

        $product->update(['stok' => 9]);
        $this->assertSame(1, ActivityLog::query()->where('subject_type', Product::class)->where('subject_id', $product->id)->count());

        $product->update(['nama' => 'Produk Diubah']);
        $this->assertDatabaseHas('activity_logs', [
            'subject_type' => Product::class,
            'subject_id' => $product->id,
            'aksi' => 'master_data.diperbarui',
            'deskripsi' => 'Produk Produk Diubah berhasil diperbarui.',
        ]);
    }

    public function test_login_is_logged_and_activity_list_is_admin_only(): void
    {
        event(new Login('web', $this->admin, false));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'aksi' => 'login',
            'subject_type' => User::class,
            'subject_id' => $this->admin->id,
        ]);
        $this->get(route('activity-logs.index'))
            ->assertOk()
            ->assertSee('Log Aktivitas')
            ->assertSee('berhasil masuk');

        $owner = User::factory()->create();
        $owner->assignRole('owner');
        $this->actingAs($owner)->get(route('activity-logs.index'))->assertForbidden();
    }

    public function test_admin_can_save_store_settings_and_number_prefixes_are_applied(): void
    {
        $this->get(route('settings.edit'))->assertOk()->assertSee('Pengaturan Toko');
        $this->patch(route('settings.update'), [
            'nama_toko' => 'Toko Contoh',
            'alamat' => 'Jalan Melati 10',
            'telepon' => '08123456789',
            'prefix_pembelian' => 'BELI',
            'prefix_penjualan' => 'JUAL',
            'persentase_pajak_default' => '11',
            'hari_jatuh_tempo_default' => 21,
        ])->assertRedirect(route('settings.edit'))->assertSessionHas('success');

        $this->assertDatabaseHas('settings', ['key' => 'nama_toko', 'value' => 'Toko Contoh']);
        $this->assertDatabaseHas('settings', ['key' => 'hari_jatuh_tempo_default', 'value' => '21']);
        $this->assertSame('BELI-'.now()->format('Ymd').'-0001', app(NomorTransaksiService::class)->nomorPembelian());
        $this->assertSame('JUAL-'.now()->format('Ymd').'-0001', app(NomorTransaksiService::class)->nomorPenjualan());
        $this->assertSame('Toko Contoh', app(StoreSettingsService::class)->forDocument()['name']);
    }

    public function test_invalid_default_tax_rate_is_rejected(): void
    {
        $this->from(route('settings.edit'))->patch(route('settings.update'), [
            'nama_toko' => 'Toko Contoh',
            'prefix_pembelian' => 'PB',
            'prefix_penjualan' => 'PJ',
            'persentase_pajak_default' => 101,
        ])->assertRedirect(route('settings.edit'))->assertSessionHasErrors('persentase_pajak_default');

        $this->assertDatabaseMissing('settings', ['key' => 'nama_toko']);
    }

    public function test_friendly_permission_and_not_found_pages_are_rendered(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('owner');

        $this->actingAs($owner)
            ->get(route('settings.edit'))
            ->assertForbidden()
            ->assertSee('Akses ditolak');

        $this->get('/halaman-tidak-ada')
            ->assertNotFound()
            ->assertSee('Halaman tidak ditemukan');
    }
}
