<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockAdjustmentRequest;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockAdjustmentController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('stok.lihat'), 403);

        $adjustments = StockAdjustment::query()
            ->with(['product', 'user', 'stockMovement'])
            ->latest('created_at')
            ->paginate(15);

        return view('stock-adjustments.index', compact('adjustments'));
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->can('stok.kelola'), 403);

        $products = Product::query()->orderBy('nama')->get(['id', 'sku', 'nama', 'stok']);

        return view('stock-adjustments.create', compact('products'));
    }

    public function store(StockAdjustmentRequest $request, StockService $stockService): RedirectResponse
    {
        $product = Product::query()->findOrFail($request->integer('product_id'));
        $stockService->sesuaikan(
            $product,
            $request->integer('qty_fisik'),
            $request->string('alasan')->trim()->toString(),
            $request->user(),
        );

        return redirect()
            ->route('stock-adjustments.index')
            ->with('success', 'Stok berhasil disesuaikan dan pergerakan stok telah dicatat.');
    }
}
