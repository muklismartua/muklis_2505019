<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserManagementService
{
    /**
     * @param  array{name: string, email: string, password: string, role: string}  $data
     */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $role = $data['role'];
            unset($data['role']);

            $user = User::query()->create($data + ['is_active' => true]);
            $user->syncRoles($role);

            return $user->load('roles');
        });
    }

    /**
     * @param  array{name: string, email: string, password?: ?string, role: string}  $data
     */
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $role = $data['role'];
            $password = $data['password'] ?? null;
            unset($data['role'], $data['password']);

            if ($user->is_active && $user->hasRole('admin') && $role !== 'admin') {
                $this->ensureAnotherActiveAdminExists($user);
            }

            $user->fill($data);

            if (filled($password)) {
                $user->password = $password;
            }

            $user->save();
            $user->syncRoles($role);

            return $user->load('roles');
        });
    }

    public function setActive(User $user, bool $isActive, User $actor): User
    {
        return DB::transaction(function () use ($user, $isActive, $actor): User {
            $managedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            if (! $isActive && $actor->is($managedUser)) {
                throw ValidationException::withMessages([
                    'is_active' => 'Anda tidak dapat menonaktifkan akun sendiri.',
                ]);
            }

            if (! $isActive && $managedUser->is_active && $managedUser->hasRole('admin')) {
                $this->ensureAnotherActiveAdminExists($managedUser);
            }

            $managedUser->is_active = $isActive;
            $managedUser->save();

            return $managedUser->load('roles');
        });
    }

    public function hasAnotherActiveAdmin(User $user): bool
    {
        return User::query()
            ->where('is_active', true)
            ->where('id', '!=', $user->id)
            ->whereHas('roles', fn ($query) => $query
                ->where('name', 'admin')
                ->where('guard_name', 'web'))
            ->exists();
    }

    private function ensureAnotherActiveAdminExists(User $user): void
    {
        if (! $this->hasAnotherActiveAdmin($user)) {
            throw ValidationException::withMessages([
                'role' => 'Akun ini adalah administrator aktif terakhir dan tidak dapat dinonaktifkan atau diturunkan rolenya.',
            ]);
        }
    }
}
