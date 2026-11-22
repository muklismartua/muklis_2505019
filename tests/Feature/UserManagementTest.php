<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
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

    public function test_admin_can_create_edit_and_deactivate_a_user(): void
    {
        $this->get(route('users.index'))->assertOk()->assertSee('Manajemen Pengguna');
        $this->get(route('users.create'))->assertOk()->assertSee('Tambah pengguna');

        $this->post(route('users.store'), [
            'name' => 'Kasir Baru',
            'email' => 'kasir.baru@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'kasir',
        ])->assertRedirect(route('users.index'));

        $user = User::query()->where('email', 'kasir.baru@example.com')->firstOrFail();
        $this->assertTrue($user->is_active);
        $this->assertSame(['kasir'], $user->getRoleNames()->all());

        $this->get(route('users.edit', $user))->assertOk()->assertSee('Edit pengguna');
        $this->put(route('users.update', $user), [
            'name' => 'Kasir Utama',
            'email' => $user->email,
            'role' => 'staf_gudang',
        ])->assertRedirect(route('users.index'));

        $user->refresh();
        $this->assertSame('Kasir Utama', $user->name);
        $this->assertSame(['staf_gudang'], $user->getRoleNames()->all());

        $this->patch(route('users.activation', $user), ['is_active' => '0'])
            ->assertRedirect(route('users.index'));
        $this->assertFalse($user->fresh()->is_active);
    }

    public function test_non_admin_cannot_access_user_management(): void
    {
        $cashier = User::factory()->create();
        $cashier->assignRole('kasir');
        $this->actingAs($cashier);

        $this->get(route('users.index'))->assertForbidden();
    }

    public function test_role_permissions_match_their_scopes(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('owner');
        $this->assertTrue($owner->can('dashboard.lihat'));
        $this->assertTrue($owner->can('laporan.lihat'));
        $this->assertFalse($owner->can('penjualan.lihat'));
        $this->assertSame(2, $owner->getAllPermissions()->count());

        $warehouseStaff = User::factory()->create();
        $warehouseStaff->assignRole('staf_gudang');
        $this->assertTrue($warehouseStaff->can('pembelian.kelola'));
        $this->assertTrue($warehouseStaff->can('stok.kelola'));
        $this->assertFalse($warehouseStaff->can('penjualan.kelola'));

        $cashier = User::factory()->create();
        $cashier->assignRole('kasir');
        $this->assertTrue($cashier->can('produk.lihat'));
        $this->assertTrue($cashier->can('pelanggan.lihat'));
        $this->assertFalse($cashier->can('pembelian.kelola'));
    }

    public function test_inactive_users_cannot_log_in_or_continue_using_a_session(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $user->assignRole('kasir');

        $this->post(route('logout'));
        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        $user->update(['is_active' => true]);
        $this->actingAs($user);
        $user->update(['is_active' => false]);

        $this->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_last_admin_cannot_be_demoted_or_deactivated(): void
    {
        $this->put(route('users.update', $this->admin), [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'role' => 'kasir',
        ])->assertSessionHasErrors('role');

        $this->patch(route('users.activation', $this->admin), ['is_active' => '0'])
            ->assertSessionHasErrors('is_active');

        $this->assertTrue($this->admin->fresh()->is_active);
        $this->assertTrue($this->admin->fresh()->hasRole('admin'));
    }
}
