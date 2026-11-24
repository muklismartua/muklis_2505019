<?php

namespace App\Http\Controllers;

use App\Http\Requests\HutangSupplierFilterRequest;
use App\Http\Requests\StoreHutangSupplierPaymentRequest;
use App\Models\HutangSupplier;
use App\Models\Supplier;
use App\Services\HutangSupplierService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HutangSupplierController extends Controller
{
    public function index(HutangSupplierFilterRequest $request): View
    {
        $filters = $request->validated();
        $hutangs = HutangSupplier::query()
            ->with(['supplier', 'purchase'])
            ->when($filters['supplier_id'] ?? null, fn ($query, $id) => $query->where('supplier_id', $id))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['jatuh_dari'] ?? null, fn ($query, $date) => $query->whereDate('jatuh_tempo', '>=', $date))
            ->when($filters['jatuh_sampai'] ?? null, fn ($query, $date) => $query->whereDate('jatuh_tempo', '<=', $date))
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [HutangSupplier::STATUS_UNPAID])
            ->orderBy('jatuh_tempo')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
        $suppliers = Supplier::query()->orderBy('nama')->pluck('nama', 'id');

        return view('supplier-debts.index', compact('hutangs', 'suppliers'));
    }

    public function show(HutangSupplier $hutangSupplier): View
    {
        $hutangSupplier->load([
            'supplier',
            'purchase.items.product',
            'payments.user',
        ]);

        return view('supplier-debts.show', compact('hutangSupplier'));
    }

    public function payment(
        StoreHutangSupplierPaymentRequest $request,
        HutangSupplier $hutangSupplier,
        HutangSupplierService $hutangSupplierService,
    ): RedirectResponse {
        $payment = $hutangSupplierService->catatPembayaran(
            $hutangSupplier,
            (int) $request->validated('jumlah'),
            $request->validated('metode'),
            $request->validated('catatan'),
            $request->user(),
        );

        return redirect()
            ->route('supplier-debts.show', $hutangSupplier)
            ->with('success', "Pembayaran {$payment->nomor} berhasil dicatat.");
    }
}
