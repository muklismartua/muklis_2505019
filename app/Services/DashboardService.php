<?php

namespace App\Services;

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

        return [
            'stats' => [
                'sales_today' => $salesToday,
                'sales_this_month' => $salesThisMonth,
                'transactions_today' => $transactionCount,
                'low_stock' => $canViewStock
                    ? Product::query()->where('aktif', true)->whereColumn('stok', '<=', 'stok_minimum')->count()
                    : null,
            ],
            'trend' => $trend,
            'days' => $days,
            'can_view_sales' => $canViewSales,
            'can_open_stock' => $user->can('stok.lihat'),
            'top_products' => $canViewSales ? $this->topProducts($salesUserId, $today) : collect(),
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
