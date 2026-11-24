<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockManualFilterRequest;
use App\Http\Requests\StoreStockManualRequest;
use App\Models\Product;
use App\Models\StockManual;
use App\Services\StokManualService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockManualController extends Controller
{
    public function index(StockManualFilterRequest $request): View
    {
        $filters = $request->validated();
        $manuals = StockManual::query()
            ->with(['product', 'user'])
            ->when($filters['dari'] ?? null, fn ($query, $date) => $query->whereDate('tanggal', '>=', $date))
            ->when($filters['sampai'] ?? null, fn ($query, $date) => $query->whereDate('tanggal', '<=', $date))
            ->when($filters['tipe'] ?? null, fn ($query, $type) => $query->where('tipe', $type))
            ->when($filters['alasan'] ?? null, fn ($query, $reason) => $query->where('alasan', $reason))
            ->when($filters['product_id'] ?? null, fn ($query, $productId) => $query->where('product_id', $productId))
            ->latest('tanggal')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();
        $products = Product::query()->orderBy('nama')->pluck('nama', 'id');
        $reasons = StockManual::REASONS;

        return view('stock-manuals.index', compact('manuals', 'products', 'reasons', 'filters'));
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->can('stok.kelola'), 403);

        $products = Product::query()
            ->orderBy('nama')
            ->get(['id', 'sku', 'nama', 'stok']);
        $selectedProductId = $request->integer('product_id');
        $reasons = StockManual::REASONS;

        return view('stock-manuals.create', compact('products', 'selectedProductId', 'reasons'));
    }

    public function store(
        StoreStockManualRequest $request,
        StokManualService $stokManualService,
    ): RedirectResponse {
        $manuals = $stokManualService->simpan($request->validated(), $request->user());

        return redirect()
            ->route('stock-manuals.index')
            ->with('success', count($manuals).' perubahan stok manual berhasil disimpan.');
    }
}
