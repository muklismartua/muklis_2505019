<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreManagedUserRequest;
use App\Http\Requests\UpdateManagedUserRequest;
use App\Http\Requests\UpdateManagedUserStatusRequest;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->with('roles')
            ->when($request->filled('q'), function ($query) use ($request): void {
                $search = $request->string('q')->trim()->toString();
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        $roles = Role::query()->where('guard_name', 'web')->orderBy('name')->get();

        return view('users.create', compact('roles'));
    }

    public function store(
        StoreManagedUserRequest $request,
        UserManagementService $service,
    ): RedirectResponse {
        $user = $service->create($request->validated());

        return redirect()
            ->route('users.index')
            ->with('success', "Pengguna {$user->name} berhasil ditambahkan.");
    }

    public function edit(User $user): View
    {
        $user->load('roles');
        $roles = Role::query()->where('guard_name', 'web')->orderBy('name')->get();

        return view('users.edit', compact('user', 'roles'));
    }

    public function update(
        UpdateManagedUserRequest $request,
        User $user,
        UserManagementService $service,
    ): RedirectResponse {
        $service->update($user, $request->validated());

        return redirect()
            ->route('users.index')
            ->with('success', "Data pengguna {$user->name} berhasil diperbarui.");
    }

    public function updateActivation(
        UpdateManagedUserStatusRequest $request,
        User $user,
        UserManagementService $service,
    ): RedirectResponse {
        $user = $service->setActive(
            $user,
            (bool) $request->validated('is_active'),
            $request->user(),
        );

        $message = $user->is_active
            ? "Akun {$user->name} berhasil diaktifkan."
            : "Akun {$user->name} berhasil dinonaktifkan.";

        return redirect()->route('users.index')->with('success', $message);
    }
}
