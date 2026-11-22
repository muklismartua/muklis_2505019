<?php

use App\Http\Controllers\InventoryTransactionController;
use App\Http\Controllers\MasterData\CategoryController;
use App\Http\Controllers\MasterData\CustomerController;
use App\Http\Controllers\MasterData\ProductController;
use App\Http\Controllers\MasterData\SupplierController;
use App\Http\Controllers\MasterData\UnitController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('transactions.landing');
})->name('home');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'active', 'verified', 'permission:dashboard.lihat'])->name('dashboard');

Route::middleware(['auth', 'active'])->group(function () {
    Route::resource('categories', CategoryController::class)
        ->middleware('permission:kategori.lihat|kategori.kelola');
    Route::resource('units', UnitController::class)
        ->middleware('permission:satuan.lihat|satuan.kelola');
    Route::resource('suppliers', SupplierController::class)
        ->middleware('permission:pemasok.lihat|pemasok.kelola');
    Route::resource('customers', CustomerController::class)
        ->middleware('permission:pelanggan.lihat|pelanggan.kelola');
    Route::resource('products', ProductController::class)
        ->middleware('permission:produk.lihat|produk.kelola');

    Route::get('/stock', [StockMovementController::class, 'index'])
        ->middleware('permission:stok.lihat')
        ->name('stock.index');
    Route::get('/stock/low-stock', [StockMovementController::class, 'lowStock'])
        ->middleware('permission:stok.lihat')
        ->name('stock.low-stock');
    Route::get('/stock/{product}', [StockMovementController::class, 'show'])
        ->middleware('permission:stok.lihat')
        ->name('stock.show');

    Route::get('/stock-adjustments', [StockAdjustmentController::class, 'index'])
        ->middleware('permission:stok.lihat')
        ->name('stock-adjustments.index');
    Route::get('/stock-adjustments/create', [StockAdjustmentController::class, 'create'])
        ->middleware('permission:stok.kelola')
        ->name('stock-adjustments.create');
    Route::post('/stock-adjustments', [StockAdjustmentController::class, 'store'])
        ->middleware('permission:stok.kelola')
        ->name('stock-adjustments.store');

    Route::get('/purchases', [PurchaseController::class, 'index'])
        ->middleware('permission:pembelian.lihat|pembelian.kelola')
        ->name('purchases.index');
    Route::get('/purchases/create', [PurchaseController::class, 'create'])
        ->middleware('permission:pembelian.kelola')
        ->name('purchases.create');
    Route::post('/purchases', [PurchaseController::class, 'store'])
        ->middleware('permission:pembelian.kelola')
        ->name('purchases.store');
    Route::get('/purchases/{purchase}', [PurchaseController::class, 'show'])
        ->middleware('permission:pembelian.lihat|pembelian.kelola')
        ->name('purchases.show');
    Route::patch('/purchases/{purchase}/cancel', [PurchaseController::class, 'cancel'])
        ->middleware('permission:pembelian.kelola')
        ->name('purchases.cancel');

    Route::get('/sales', [SaleController::class, 'index'])
        ->middleware('permission:penjualan.lihat|penjualan.kelola')
        ->name('sales.index');
    Route::get('/sales/create', [SaleController::class, 'create'])
        ->middleware('permission:penjualan.kelola')
        ->name('sales.create');
    Route::post('/sales', [SaleController::class, 'store'])
        ->middleware('permission:penjualan.kelola')
        ->name('sales.store');
    Route::get('/sales/{sale}', [SaleController::class, 'show'])
        ->middleware('permission:penjualan.lihat|penjualan.kelola')
        ->name('sales.show');
    Route::patch('/sales/{sale}/cancel', [SaleController::class, 'cancel'])
        ->middleware('permission:penjualan.kelola')
        ->name('sales.cancel');

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
