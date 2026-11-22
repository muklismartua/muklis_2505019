<?php

use App\Http\Controllers\InventoryTransactionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('transactions.landing');
})->name('home');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'active', 'verified', 'permission:dashboard.lihat'])->name('dashboard');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/transactions', [InventoryTransactionController::class, 'index'])
        ->middleware('permission:penjualan.lihat|pembelian.lihat')
        ->name('transactions.index');
    Route::get('/transactions/create', [InventoryTransactionController::class, 'create'])
        ->middleware('permission:penjualan.kelola|pembelian.kelola')
        ->name('transactions.create');
    Route::post('/transactions', [InventoryTransactionController::class, 'store'])
        ->middleware('permission:penjualan.kelola|pembelian.kelola')
        ->name('transactions.store');
    Route::get('/transactions/{transaction}', [InventoryTransactionController::class, 'show'])
        ->middleware('permission:penjualan.lihat|pembelian.lihat')
        ->name('transactions.show');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware(['role:admin', 'permission:user.kelola'])->group(function () {
        Route::resource('users', UserManagementController::class)
            ->except(['show', 'destroy']);
        Route::patch('/users/{user}/activation', [UserManagementController::class, 'updateActivation'])
            ->name('users.activation');
    });
});

require __DIR__.'/auth.php';
