<?php

use App\Http\Controllers\InventoryTransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('transactions.landing');
});

Route::resource('transactions', InventoryTransactionController::class)
    ->only(['index', 'create', 'store', 'show']);
