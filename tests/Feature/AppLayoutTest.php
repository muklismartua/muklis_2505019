<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_dashboard_renders_the_shell_and_four_placeholder_cards(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Master Data')
            ->assertSee('Stok')
            ->assertSee('Pembelian')
            ->assertSee('Penjualan')
            ->assertSee('Pengaturan')
            ->assertSee($admin->name)
            ->assertSee('Keluar');

        $this->assertSame(4, substr_count($response->getContent(), 'aria-label="Kartu ringkasan'));
    }

    public function test_sidebar_shows_only_items_allowed_for_the_user_role(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('owner');

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Laporan')
            ->assertDontSee('Master Data')
            ->assertDontSee('Transaksi')
            ->assertDontSee('Pengguna')
            ->assertDontSee('Pengaturan');
    }
}
