<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInventoryTransactionRequest;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Services\InventoryTransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryTransactionController extends Controller
{
    public function index(Request $request): View
    {
        $transactions = InventoryTransaction::query()
            ->with('user')
            ->when($request->filled('q'), function ($query) use ($request): void {
                $search = $request->string('q')->trim()->toString();
                $query->where(function ($query) use ($search): void {
                    $query->where('transaction_number', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->input('type')))
            ->latest('transaction_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('transactions.index', compact('transactions'));
    }

    public function create(): View
    {
        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'sku', 'name', 'selling_price', 'purchase_price', 'stock']);

        return view('transactions.create', compact('products'));
    }

    public function store(
        StoreInventoryTransactionRequest $request,
        InventoryTransactionService $service,
    ): RedirectResponse {
        $transaction = $service->create($request->validated(), $request->user());

        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', 'Transaksi berhasil disimpan.');
    }

    public function show(InventoryTransaction $transaction): View
    {
        $transaction->load(['items', 'user']);

        return view('transactions.show', compact('transaction'));
    }
}
