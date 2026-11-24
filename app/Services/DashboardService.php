<?php

namespace App\Services;

use App\Models\HutangSupplier;
use App\Models\Piutang;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function data(User $user, int $days): array
    {
        $hasFullAccess = $user->hasRole('owner') || $user->hasRole('admin');
        $isCashier = $user->hasRole('kasir');
        $canViewSales = $hasFullAccess || $user->can('penjualan.lihat');
        $canViewPurchases = $hasFullAccess || $user->can('pembelian.lihat');
        $canViewStock = $hasFullAccess || $user->can('stok.lihat');
        $canViewReceivables = $hasFullAccess || $user->canAny(['piutang.lihat', 'piutang.kelola']);
        $canViewSupplierDebts = $hasFullAccess || $user->canAny(['hutang.lihat', 'hutang.kelola']);
        $salesUserId = $isCashier ? $user->id : null;
        $today = Carbon::today();
        $todayStart = $today->toDateString();
        $monthStart = $today->copy()->startOfMonth()->toDateString();

        $salesToday = $canViewSales
            ? $this->completedSales($salesUserId)->whereDate('tanggal', $todayStart)->sum('total')
            : null;
        $salesThisMonth = $canViewSales
            ? $this->completedSales($salesUserId)
                ->whereDate('tanggal', '>=', $monthStart)
                ->whereDate('tanggal', '<=', $todayStart)
                ->sum('total')
            : null;
        $cashReceivedToday = $canViewSales
            ? $this->cashReceivedBetween($today, $today, $salesUserId)
            : null;
        $cashReceivedThisMonth = $canViewSales
            ? $this->cashReceivedBetween($today->copy()->startOfMonth(), $today, $salesUserId)
            : null;

        $transactionCount = 0;
        if ($canViewSales) {
            $transactionCount += Sale::query()
                ->where('status', Sale::STATUS_COMPLETED)
                ->whereDate('tanggal', $todayStart)
                ->when($salesUserId, fn (Builder $query, int $id) => $query->where('user_id', $id))
                ->count();
        }
        if ($canViewPurchases) {
            $transactionCount += Purchase::query()
                ->where('status', Purchase::STATUS_COMPLETED)
                ->whereDate('tanggal', $todayStart)
                ->count();
        }

        $trend = $canViewSales
            ? $this->salesTrend($salesUserId, $days, $today)
            : ['labels' => [], 'values' => []];
        $receivableQuery = Piutang::query()
            ->where('status', Piutang::STATUS_UNPAID)
            ->when($salesUserId, fn (Builder $query, int $id) => $query->whereHas(
                'sale',
                fn (Builder $saleQuery) => $saleQuery->where('user_id', $id),
            ));
        $overdueQuery = (clone $receivableQuery)
            ->whereNotNull('jatuh_tempo')
            ->whereDate('jatuh_tempo', '<', $todayStart);
        $supplierDebtQuery = HutangSupplier::query()
            ->where('status', HutangSupplier::STATUS_UNPAID);
        $overdueSupplierDebtQuery = (clone $supplierDebtQuery)
            ->whereNotNull('jatuh_tempo')
            ->whereDate('jatuh_tempo', '<', $todayStart);

        return [
            'stats' => [
                'sales_today' => $salesToday,
                'sales_this_month' => $salesThisMonth,
                'cash_received_today' => $cashReceivedToday,
                'cash_received_this_month' => $cashReceivedThisMonth,
                'transactions_today' => $transactionCount,
                'low_stock' => $canViewStock
                    ? Product::query()->where('aktif', true)->whereColumn('stok', '<=', 'stok_minimum')->count()
                    : null,
                'active_receivables' => $canViewReceivables ? (int) $receivableQuery->sum('sisa') : null,
                'overdue_receivable_count' => $canViewReceivables ? (clone $overdueQuery)->count() : null,
                'overdue_receivable_total' => $canViewReceivables ? (int) $overdueQuery->sum('sisa') : null,
                'active_supplier_debts' => $canViewSupplierDebts ? (int) $supplierDebtQuery->sum('sisa') : null,
                'overdue_supplier_debt_count' => $canViewSupplierDebts ? (clone $overdueSupplierDebtQuery)->count() : null,
                'overdue_supplier_debt_total' => $canViewSupplierDebts ? (int) $overdueSupplierDebtQuery->sum('sisa') : null,
            ],
            'trend' => $trend,
            'days' => $days,
            'can_view_sales' => $canViewSales,
            'can_view_receivables' => $canViewReceivables,
            'can_view_supplier_debts' => $canViewSupplierDebts,
            'can_open_stock' => $user->can('stok.lihat'),
            'top_products' => $canViewSales ? $this->topProducts($salesUserId, $today) : collect(),
            'top_debtors' => $canViewReceivables ? $this->topDebtors($salesUserId) : collect(),
            'recent_transactions' => $this->recentTransactions(
                $salesUserId,
                $canViewSales,
                $canViewPurchases,
            ),
        ];
    }

    private function completedSales(?int $userId): Builder
    {
        return Sale::query()
            ->where('status', Sale::STATUS_COMPLETED)
            ->when($userId, fn (Builder $query, int $id) => $query->where('user_id', $id));
    }

    private function cashReceivedBetween(Carbon $start, Carbon $end, ?int $userId): int
    {
        $saleReceipts = Sale::query()
            ->where('status', Sale::STATUS_COMPLETED)
            ->whereDate('tanggal', '>=', $start->toDateString())
            ->whereDate('tanggal', '<=', $end->toDateString())
            ->when($userId, fn (Builder $query, int $id) => $query->where('user_id', $id))
            ->selectRaw('COALESCE(SUM(CASE WHEN bayar < total THEN bayar ELSE total END), 0) AS total')
            ->value('total');

        $installmentReceipts = DB::table('piutang_payments')
            ->join('piutangs', 'piutangs.id', '=', 'piutang_payments.piutang_id')
            ->join('sales', 'sales.id', '=', 'piutangs.sale_id')
            ->where('sales.status', Sale::STATUS_COMPLETED)
            ->whereDate('piutang_payments.tanggal', '>=', $start->toDateString())
            ->whereDate('piutang_payments.tanggal', '<=', $end->toDateString())
            ->when($userId, fn ($query, int $id) => $query->where('sales.user_id', $id))
            ->sum('piutang_payments.jumlah');

        return (int) $saleReceipts + (int) $installmentReceipts;
    }

    private function topDebtors(?int $salesUserId): Collection
    {
        return DB::table('piutangs')
            ->join('customers', 'customers.id', '=', 'piutangs.customer_id')
            ->join('sales', 'sales.id', '=', 'piutangs.sale_id')
            ->where('piutangs.status', Piutang::STATUS_UNPAID)
            ->when($salesUserId, fn ($query, int $id) => $query->where('sales.user_id', $id))
            ->select('customers.id', 'customers.nama')
            ->selectRaw('SUM(piutangs.sisa) AS total_hutang')
            ->groupBy('customers.id', 'customers.nama')
            ->orderByDesc('total_hutang')
            ->orderBy('customers.nama')
            ->limit(5)
            ->get();
    }

    private function salesTrend(?int $userId, int $days, Carbon $today): array
    {
        $start = $today->copy()->subDays($days - 1);
        $dailyTotals = $this->completedSales($userId)
            ->whereDate('tanggal', '>=', $start->toDateString())
            ->whereDate('tanggal', '<=', $today->toDateString())
            ->select('tanggal')
            ->selectRaw('SUM(total) AS total')
            ->groupBy('tanggal')
            ->get()
            ->keyBy(fn ($row) => Carbon::parse($row->tanggal)->toDateString());

        $labels = [];
        $values = [];
        for ($date = $start->copy(); $date->lte($today); $date->addDay()) {
            $key = $date->toDateString();
            $labels[] = $date->format('d/m');
            $values[] = (int) ($dailyTotals->get($key)->total ?? 0);
        }

        return compact('labels', 'values');
    }

    private function topProducts(?int $userId, Carbon $today): Collection
    {
        $start = $today->copy()->subDays(29)->toDateString();

        return DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.status', Sale::STATUS_COMPLETED)
            ->whereDate('sales.tanggal', '>=', $start)
            ->whereDate('sales.tanggal', '<=', $today->toDateString())
            ->when($userId, fn ($query, int $id) => $query->where('sales.user_id', $id))
            ->select('products.id', 'products.nama', 'products.sku')
            ->selectRaw('SUM(sale_items.qty) AS qty_terjual')
            ->selectRaw('SUM(sale_items.subtotal) AS total_penjualan')
            ->groupBy('products.id', 'products.nama', 'products.sku')
            ->orderByDesc('qty_terjual')
            ->limit(5)
            ->get();
    }

    private function recentTransactions(
        ?int $salesUserId,
        bool $canViewSales,
        bool $canViewPurchases,
    ): Collection {
        $transactions = collect();

        if ($canViewSales) {
            $sales = Sale::query()
                ->with('customer')
                ->when($salesUserId, fn (Builder $query, int $id) => $query->where('user_id', $id))
                ->latest('created_at')
                ->limit(5)
                ->get()
                ->map(fn (Sale $sale) => [
                    'jenis' => 'Penjualan',
                    'nomor' => $sale->nomor,
                    'tanggal' => $sale->tanggal,
                    'total' => $sale->total,
                    'status' => $sale->status,
                    'tujuan' => route('sales.show', $sale),
                    'created_at' => $sale->created_at,
                ]);
            $transactions = $transactions->concat($sales);
        }

        if ($canViewPurchases) {
            $purchases = Purchase::query()
                ->with('supplier')
                ->latest('created_at')
                ->limit(5)
                ->get()
                ->map(fn (Purchase $purchase) => [
                    'jenis' => 'Pembelian',
                    'nomor' => $purchase->nomor,
                    'tanggal' => $purchase->tanggal,
                    'total' => $purchase->total,
                    'status' => $purchase->status,
                    'tujuan' => route('purchases.show', $purchase),
                    'created_at' => $purchase->created_at,
                ]);
            $transactions = $transactions->concat($purchases);
        }

        return $transactions
            ->sortByDesc(fn (array $transaction) => $transaction['created_at'])
            ->take(5)
            ->values();
    }
}
