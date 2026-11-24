<?php

namespace App\Http\Controllers;

use App\Http\Requests\PiutangFilterRequest;
use App\Http\Requests\StorePiutangPaymentRequest;
use App\Models\Customer;
use App\Models\Piutang;
use App\Services\PiutangService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PiutangController extends Controller
{
    public function index(PiutangFilterRequest $request): View
    {
        $filters = $request->validated();
        $piutangs = Piutang::query()
            ->with(['customer', 'sale'])
            ->when($filters['customer_id'] ?? null, fn ($query, $id) => $query->where('customer_id', $id))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['jatuh_tempo'] ?? null, fn ($query, $date) => $query->whereDate('jatuh_tempo', $date))
            ->orderByRaw('jatuh_tempo is null')
            ->orderBy('jatuh_tempo')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();
        $customers = Customer::query()->orderBy('nama')->pluck('nama', 'id');

        return view('piutangs.index', compact('piutangs', 'customers', 'filters'));
    }

    public function show(Request $request, Piutang $piutang): View
    {
        abort_unless($request->user()->canAny(['piutang.lihat', 'piutang.kelola']), 403);

        $piutang->load([
            'customer',
            'sale.items.product',
            'payments' => fn ($query) => $query->with('user')->latest('tanggal')->latest('id'),
        ]);

        return view('piutangs.show', compact('piutang'));
    }

    public function payment(
        StorePiutangPaymentRequest $request,
        Piutang $piutang,
        PiutangService $piutangService,
    ): RedirectResponse {
        $data = $request->validated();
        $piutangService->catatPembayaran(
            $piutang,
            (int) $data['jumlah'],
            $data['metode'],
            $data['catatan'] ?? null,
        );

        return redirect()
            ->route('piutangs.show', $piutang)
            ->with('success', 'Pembayaran piutang berhasil dicatat.');
    }
}
