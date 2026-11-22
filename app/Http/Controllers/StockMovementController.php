<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockMovementFilterRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()
            ->with('unit')
            ->when($request->filled('q'), function ($query) use ($request): void {
                $search = $request->string('q')->trim()->toString();
                $query->where(function ($query) use ($search): void {
                    $query->where('sku', 'like', "%{$search}%")
                        ->orWhere('nama', 'like', "%{$search}%");
                });
            })
            ->orderBy('nama')
            ->paginate(15)
            ->withQueryString();

        return view('stock.index', compact('products'));
    }

    public function show(StockMovementFilterRequest $request, Product $product): View
    {
        $filters = $request->validated();
        $movements = $product->stockMovements()
            ->with(['user', 'referensi'])
            ->when($filters['dari'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['sampai'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->latest('created_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('stock.show', compact('product', 'movements'));
    }

    public function lowStock(): View
    {
        $products = Product::query()
            ->with('unit')
            ->whereColumn('stok', '<=', 'stok_minimum')
            ->orderBy('nama')
            ->paginate(15)
            ->withQueryString();

        return view('stock.low-stock', compact('products'));
    }
}
