<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'dashboard.lihat',
            'produk.lihat',
            'produk.kelola',
            'kategori.lihat',
            'kategori.kelola',
            'satuan.lihat',
            'satuan.kelola',
            'pelanggan.lihat',
            'pelanggan.kelola',
            'pelanggan.buat',
            'pemasok.lihat',
            'pemasok.kelola',
            'stok.lihat',
            'stok.kelola',
            'pembelian.lihat',
            'pembelian.kelola',
            'penjualan.lihat',
            'penjualan.kelola',
            'piutang.lihat',
            'piutang.kelola',
            'hutang.lihat',
            'hutang.kelola',
            'laporan.lihat',
            'user.kelola',
            'pengaturan.kelola',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $rolePermissions = [
            'admin' => Permission::query()->where('guard_name', 'web')->get(),
            'staf_gudang' => [
                'dashboard.lihat',
                'produk.lihat',
                'produk.kelola',
                'kategori.lihat',
                'kategori.kelola',
                'satuan.lihat',
                'satuan.kelola',
                'pemasok.lihat',
                'pemasok.kelola',
                'stok.lihat',
                'stok.kelola',
                'pembelian.lihat',
                'pembelian.kelola',
                'hutang.lihat',
                'hutang.kelola',
            ],
            'kasir' => [
                'dashboard.lihat',
                'produk.lihat',
                'pelanggan.lihat',
                'pelanggan.buat',
                'penjualan.lihat',
                'penjualan.kelola',
                'piutang.kelola',
            ],
            'owner' => [
                'dashboard.lihat',
                'laporan.lihat',
                'piutang.lihat',
                'hutang.lihat',
            ],
        ];

        foreach ($rolePermissions as $roleName => $rolePermissionNames) {
            $role = Role::findOrCreate($roleName, 'web');
            $permissionsForRole = $rolePermissionNames instanceof Collection
                ? $rolePermissionNames
                : Permission::query()
                    ->where('guard_name', 'web')
                    ->whereIn('name', $rolePermissionNames)
                    ->get();

            $role->syncPermissions($permissionsForRole);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
