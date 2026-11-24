<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelSaleRequest;
use App\Http\Requests\SaleFilterRequest;
use App\Http\Requests\StoreSaleRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Services\PenjualanService;
use App\Services\StoreSettingsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
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

    public function create(Request $request, StoreSettingsService $storeSettingsService): View
    {
        abort_unless($request->user()->can('penjualan.kelola'), 403);

        $selectedCustomer = old('customer_id')
            ? Customer::query()->where('aktif', true)->find(old('customer_id'))
            : null;
        $customerSearchUrl = route('customers.search');
        $quickCustomerUrl = route('customers.quick-store');
        $products = Product::query()
            ->where('aktif', true)
            ->orderBy('nama')
            ->get(['id', 'sku', 'nama', 'harga_jual', 'stok']);
        $defaultTaxRate = (float) $storeSettingsService->get('persentase_pajak_default');

        return view('sales.create', compact(
            'selectedCustomer',
            'customerSearchUrl',
            'quickCustomerUrl',
            'products',
            'defaultTaxRate',
        ));
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
        $sale->load(['items.product', 'customer', 'user', 'receivable']);

        return view('sales.show', compact('sale'));
    }

    public function pdf(Request $request, Sale $sale, StoreSettingsService $storeSettingsService): Response
    {
        abort_unless($request->user()->canAny(['penjualan.lihat', 'penjualan.kelola']), 403);
        $sale->load(['items.product', 'customer', 'user', 'receivable']);
        $store = $storeSettingsService->forDocument();
        $isCancelled = $sale->status === Sale::STATUS_CANCELLED;

        return Pdf::loadView('pdf.sales.invoice', compact('sale', 'store', 'isCancelled'))
            ->setPaper('a4', 'portrait')
            ->download("invoice-{$sale->nomor}.pdf");
    }

    public function receipt(
        Request $request,
        Sale $sale,
        StoreSettingsService $storeSettingsService,
    ): Response
    {
        abort_unless($request->user()->canAny(['penjualan.lihat', 'penjualan.kelola']), 403);
        $sale->load(['items.product', 'customer', 'user', 'receivable']);
        $store = $storeSettingsService->forDocument();
        $isCancelled = $sale->status === Sale::STATUS_CANCELLED;
        $paperHeightMm = max(120, 96 + ($sale->items->count() * 15));
        $paperHeightPoints = $paperHeightMm * 72 / 25.4;

        return Pdf::loadView('pdf.sales.receipt', compact('sale', 'store', 'isCancelled'))
            ->setPaper([0, 0, 226.77, $paperHeightPoints])
            ->download("nota-{$sale->nomor}.pdf");
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
