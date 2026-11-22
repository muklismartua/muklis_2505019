<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->call(RolePermissionSeeder::class);

        foreach ([
            ['name' => 'Administrator', 'email' => 'admin@example.com', 'role' => 'admin'],
            ['name' => 'Staf Gudang', 'email' => 'staf_gudang@example.com', 'role' => 'staf_gudang'],
            ['name' => 'Kasir', 'email' => 'kasir@example.com', 'role' => 'kasir'],
            ['name' => 'Owner', 'email' => 'owner@example.com', 'role' => 'owner'],
        ] as $account) {
            $user = User::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => 'password',
                    'is_active' => true,
                ],
            );
            $user->forceFill(['email_verified_at' => now()])->save();
            $user->syncRoles($account['role']);
        }
    }
}
