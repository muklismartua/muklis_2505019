<?php

use App\Http\Controllers\InventoryTransactionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\MasterData\CategoryController;
use App\Http\Controllers\MasterData\CustomerController;
use App\Http\Controllers\MasterData\ProductController;
use App\Http\Controllers\MasterData\SupplierController;
use App\Http\Controllers\MasterData\UnitController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockManualController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('transactions.landing');
})->name('home');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'active', 'verified', 'permission:dashboard.lihat'])
    ->name('dashboard');

Route::middleware(['auth', 'active'])->group(function () {
    Route::resource('categories', CategoryController::class)
        ->middleware('permission:kategori.lihat|kategori.kelola');
    Route::resource('units', UnitController::class)
        ->middleware('permission:satuan.lihat|satuan.kelola');
    Route::resource('suppliers', SupplierController::class)
        ->middleware('permission:pemasok.lihat|pemasok.kelola');
    Route::get('/pelanggan/cari', [CustomerController::class, 'search'])
        ->middleware('permission:pelanggan.lihat')
        ->name('customers.search');
    Route::post('/pelanggan/cepat', [CustomerController::class, 'quickStore'])
        ->middleware('permission:pelanggan.buat|penjualan.kelola')
        ->name('customers.quick-store');
    Route::resource('customers', CustomerController::class)
        ->except(['destroy'])
        ->middleware('permission:pelanggan.lihat|pelanggan.kelola');
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])
        ->middleware(['role:admin', 'permission:pelanggan.kelola'])
        ->name('customers.destroy');
    Route::resource('products', ProductController::class)
        ->middleware('permission:produk.lihat|produk.kelola');

    Route::get('/reports', [ReportController::class, 'home'])
        ->middleware('permission:laporan.lihat')
        ->name('reports.index');
    Route::get('/reports/{report}/excel', [ReportController::class, 'excel'])
        ->middleware('permission:laporan.lihat')
        ->name('reports.excel');
    Route::get('/reports/{report}/pdf', [ReportController::class, 'pdf'])
        ->middleware('permission:laporan.lihat')
        ->name('reports.pdf');
    Route::get('/reports/{report}', [ReportController::class, 'index'])
        ->middleware('permission:laporan.lihat')
        ->name('reports.show');

    Route::middleware('role:admin')->group(function () {
        Route::get('/activity-logs', [ActivityLogController::class, 'index'])
            ->name('activity-logs.index');
    });

    Route::middleware(['role:admin', 'permission:pengaturan.kelola'])->group(function () {
        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::patch('/settings', [SettingsController::class, 'update'])->name('settings.update');
    });

    Route::get('/stock', [StockMovementController::class, 'index'])
        ->middleware('permission:stok.lihat')
        ->name('stock.index');
    Route::get('/stock/low-stock', [StockMovementController::class, 'lowStock'])
        ->middleware('permission:stok.lihat')
        ->name('stock.low-stock');
    Route::get('/stock-manuals', [StockManualController::class, 'index'])
        ->middleware('permission:stok.lihat|stok.kelola')
        ->name('stock-manuals.index');
    Route::get('/stock-manuals/create', [StockManualController::class, 'create'])
        ->middleware('permission:stok.kelola')
        ->name('stock-manuals.create');
    Route::post('/stock-manuals', [StockManualController::class, 'store'])
        ->middleware('permission:stok.kelola')
        ->name('stock-manuals.store');
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
    Route::get('/purchases/{purchase}/pdf', [PurchaseController::class, 'pdf'])
        ->middleware('permission:pembelian.lihat|pembelian.kelola')
        ->name('purchases.pdf');
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
    Route::get('/sales/{sale}/pdf', [SaleController::class, 'pdf'])
        ->middleware('permission:penjualan.lihat|penjualan.kelola')
        ->name('sales.pdf');
    Route::get('/sales/{sale}/receipt', [SaleController::class, 'receipt'])
        ->middleware('permission:penjualan.lihat|penjualan.kelola')
        ->name('sales.receipt');
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
