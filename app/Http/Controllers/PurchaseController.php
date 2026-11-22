<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelPurchaseRequest;
use App\Http\Requests\PurchaseFilterRequest;
use App\Http\Requests\StorePurchaseRequest;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PembelianService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function index(PurchaseFilterRequest $request): View
    {
        $filters = $request->validated();
        $purchases = Purchase::query()
            ->with(['supplier', 'user'])
            ->when($filters['dari'] ?? null, fn ($query, $date) => $query->whereDate('tanggal', '>=', $date))
            ->when($filters['sampai'] ?? null, fn ($query, $date) => $query->whereDate('tanggal', '<=', $date))
            ->when($filters['supplier_id'] ?? null, fn ($query, $id) => $query->where('supplier_id', $id))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest('tanggal')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
        $suppliers = Supplier::query()->orderBy('nama')->pluck('nama', 'id');

        return view('purchases.index', compact('purchases', 'suppliers'));
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->can('pembelian.kelola'), 403);

        $suppliers = Supplier::query()->orderBy('nama')->pluck('nama', 'id');
        $products = Product::query()
            ->where('aktif', true)
            ->orderBy('nama')
            ->get(['id', 'sku', 'nama', 'harga_beli', 'stok']);

        return view('purchases.create', compact('suppliers', 'products'));
    }

    public function store(StorePurchaseRequest $request, PembelianService $pembelianService): RedirectResponse
    {
        $purchase = $pembelianService->simpan($request->validated(), $request->user());

        return redirect()
            ->route('purchases.show', $purchase)
            ->with('success', 'Pembelian berhasil disimpan dan stok telah ditambahkan.');
    }

    public function show(Request $request, Purchase $purchase): View
    {
        abort_unless($request->user()->canAny(['pembelian.lihat', 'pembelian.kelola']), 403);
        $purchase->load(['items.product', 'supplier', 'user']);

        return view('purchases.show', compact('purchase'));
    }

    public function pdf(Request $request, Purchase $purchase): Response
    {
        abort_unless($request->user()->canAny(['pembelian.lihat', 'pembelian.kelola']), 403);
        $purchase->load(['items.product', 'supplier', 'user']);
        $store = config('store');
        $isCancelled = $purchase->status === Purchase::STATUS_CANCELLED;

        return Pdf::loadView('pdf.purchases.receipt', compact('purchase', 'store', 'isCancelled'))
            ->setPaper('a4', 'portrait')
            ->download("bukti-pembelian-{$purchase->nomor}.pdf");
    }

    public function cancel(
        CancelPurchaseRequest $request,
        Purchase $purchase,
        PembelianService $pembelianService,
    ): RedirectResponse {
        $pembelianService->batalkan($purchase, $request->user());

        return redirect()
            ->route('purchases.show', $purchase)
            ->with('success', 'Pembelian berhasil dibatalkan dan stok telah dikurangi kembali.');
    }
}
