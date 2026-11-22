<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelSaleRequest;
use App\Http\Requests\SaleFilterRequest;
use App\Http\Requests\StoreSaleRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Services\PenjualanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function index(SaleFilterRequest $request): View
    {
        $filters = $request->validated();
        $sales = Sale::query()
            ->with(['customer', 'user'])
            ->when($filters['dari'] ?? null, fn ($query, $date) => $query->whereDate('tanggal', '>=', $date))
            ->when($filters['sampai'] ?? null, fn ($query, $date) => $query->whereDate('tanggal', '<=', $date))
            ->when($filters['customer_id'] ?? null, fn ($query, $id) => $query->where('customer_id', $id))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['metode_pembayaran'] ?? null, fn ($query, $method) => $query->where('metode_pembayaran', $method))
            ->latest('tanggal')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
        $customers = Customer::query()->orderBy('nama')->pluck('nama', 'id');

        return view('sales.index', compact('sales', 'customers'));
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->can('penjualan.kelola'), 403);

        $customers = Customer::query()->orderBy('nama')->pluck('nama', 'id');
        $products = Product::query()
            ->where('aktif', true)
            ->orderBy('nama')
            ->get(['id', 'sku', 'nama', 'harga_jual', 'stok']);

        return view('sales.create', compact('customers', 'products'));
    }

    public function store(StoreSaleRequest $request, PenjualanService $penjualanService): RedirectResponse
    {
        $sale = $penjualanService->simpan($request->validated(), $request->user());

        return redirect()
            ->route('sales.show', $sale)
            ->with('success', 'Penjualan berhasil disimpan dan stok telah dikurangi.');
    }

    public function show(Request $request, Sale $sale): View
    {
        abort_unless($request->user()->canAny(['penjualan.lihat', 'penjualan.kelola']), 403);
        $sale->load(['items.product', 'customer', 'user']);

        return view('sales.show', compact('sale'));
    }

    public function cancel(
        CancelSaleRequest $request,
        Sale $sale,
        PenjualanService $penjualanService,
    ): RedirectResponse {
        $penjualanService->batalkan($sale, $request->user());

        return redirect()
            ->route('sales.show', $sale)
            ->with('success', 'Penjualan berhasil dibatalkan dan stok telah dikembalikan.');
    }
}
