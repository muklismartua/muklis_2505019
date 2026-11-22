<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    private const DEFINITIONS = [
        'penjualan-tanggal' => [
            'title' => 'Laporan Penjualan per Tanggal',
            'description' => 'Ringkasan transaksi penjualan selesai menurut tanggal.',
            'filters' => ['periode' => true],
            'columns' => [
                ['key' => 'tanggal', 'label' => 'Tanggal', 'type' => 'date'],
                ['key' => 'transaksi', 'label' => 'Transaksi', 'type' => 'number'],
                ['key' => 'penjualan', 'label' => 'Penjualan', 'type' => 'money'],
                ['key' => 'diskon', 'label' => 'Diskon', 'type' => 'money'],
                ['key' => 'pajak', 'label' => 'Pajak', 'type' => 'money'],
            ],
        ],
        'penjualan-produk' => [
            'title' => 'Laporan Penjualan per Produk',
            'description' => 'Jumlah dan subtotal item produk pada transaksi selesai.',
            'filters' => ['periode' => true, 'produk' => true],
            'columns' => [
                ['key' => 'sku', 'label' => 'SKU', 'type' => 'text'],
                ['key' => 'produk', 'label' => 'Produk', 'type' => 'text'],
                ['key' => 'qty', 'label' => 'Qty Terjual', 'type' => 'number'],
                ['key' => 'penjualan', 'label' => 'Subtotal Item', 'type' => 'money'],
            ],
        ],
        'penjualan-pelanggan' => [
            'title' => 'Laporan Penjualan per Pelanggan',
            'description' => 'Ringkasan nilai transaksi selesai menurut pelanggan.',
            'filters' => ['periode' => true, 'pelanggan' => true],
            'columns' => [
                ['key' => 'pelanggan', 'label' => 'Pelanggan', 'type' => 'text'],
                ['key' => 'transaksi', 'label' => 'Transaksi', 'type' => 'number'],
                ['key' => 'penjualan', 'label' => 'Penjualan', 'type' => 'money'],
            ],
        ],
        'pembelian-tanggal' => [
            'title' => 'Laporan Pembelian per Tanggal',
            'description' => 'Ringkasan transaksi pembelian selesai menurut tanggal.',
            'filters' => ['periode' => true],
            'columns' => [
                ['key' => 'tanggal', 'label' => 'Tanggal', 'type' => 'date'],
                ['key' => 'transaksi', 'label' => 'Transaksi', 'type' => 'number'],
                ['key' => 'pembelian', 'label' => 'Pembelian', 'type' => 'money'],
                ['key' => 'diskon', 'label' => 'Diskon', 'type' => 'money'],
            ],
        ],
        'pembelian-supplier' => [
            'title' => 'Laporan Pembelian per Supplier',
            'description' => 'Ringkasan nilai transaksi selesai menurut supplier.',
            'filters' => ['periode' => true, 'supplier' => true],
            'columns' => [
                ['key' => 'supplier', 'label' => 'Supplier', 'type' => 'text'],
                ['key' => 'transaksi', 'label' => 'Transaksi', 'type' => 'number'],
                ['key' => 'pembelian', 'label' => 'Pembelian', 'type' => 'money'],
            ],
        ],
        'stok-saat-ini' => [
            'title' => 'Laporan Stok Saat Ini',
            'description' => 'Jumlah stok dan nilai persediaan berdasarkan harga beli produk.',
            'filters' => [],
            'columns' => [
                ['key' => 'sku', 'label' => 'SKU', 'type' => 'text'],
                ['key' => 'produk', 'label' => 'Produk', 'type' => 'text'],
                ['key' => 'kategori', 'label' => 'Kategori', 'type' => 'text'],
                ['key' => 'stok', 'label' => 'Stok', 'type' => 'number'],
                ['key' => 'harga_beli', 'label' => 'Harga Beli', 'type' => 'money'],
                ['key' => 'nilai_persediaan', 'label' => 'Nilai Persediaan', 'type' => 'money'],
            ],
        ],
        'mutasi-stok' => [
            'title' => 'Laporan Mutasi Stok per Produk',
            'description' => 'Riwayat seluruh pergerakan stok dalam periode yang dipilih.',
            'filters' => ['periode' => true, 'produk' => true],
            'columns' => [
                ['key' => 'tanggal', 'label' => 'Tanggal', 'type' => 'datetime'],
                ['key' => 'sku', 'label' => 'SKU', 'type' => 'text'],
                ['key' => 'produk', 'label' => 'Produk', 'type' => 'text'],
                ['key' => 'tipe', 'label' => 'Tipe', 'type' => 'text'],
                ['key' => 'qty', 'label' => 'Qty', 'type' => 'number'],
                ['key' => 'stok_sebelum', 'label' => 'Stok Sebelum', 'type' => 'number'],
                ['key' => 'stok_sesudah', 'label' => 'Stok Sesudah', 'type' => 'number'],
                ['key' => 'referensi', 'label' => 'Referensi', 'type' => 'text'],
                ['key' => 'pengguna', 'label' => 'Pengguna', 'type' => 'text'],
            ],
        ],
        'laba-rugi' => [
            'title' => 'Laporan Laba Rugi Sederhana',
            'description' => 'Penjualan setelah diskon dan sebelum pajak dikurangi HPP berdasarkan harga beli produk saat ini.',
            'filters' => ['periode' => true],
            'columns' => [
                ['key' => 'tanggal', 'label' => 'Tanggal', 'type' => 'date'],
                ['key' => 'penjualan', 'label' => 'Penjualan (di luar pajak)', 'type' => 'money'],
                ['key' => 'hpp', 'label' => 'HPP Saat Ini', 'type' => 'money'],
                ['key' => 'laba', 'label' => 'Laba/Rugi', 'type' => 'money'],
            ],
        ],
    ];

    public function supports(string $report): bool
    {
        return isset(self::DEFINITIONS[$report]);
    }

    public function definition(string $report): array
    {
        abort_unless($this->supports($report), 404);

        return self::DEFINITIONS[$report];
    }

    public function definitions(): array
    {
        return self::DEFINITIONS;
    }

    public function stockValueTotal(): int
    {
        return (int) DB::table('products')
            ->whereNull('deleted_at')
            ->selectRaw('COALESCE(SUM(stok * harga_beli), 0) AS total')
            ->value('total');
    }

    public function normalizeFilters(array $filters): array
    {
        $today = Carbon::today();

        return [
            ...$filters,
            'dari' => Carbon::parse($filters['dari'] ?? $today->copy()->startOfMonth())->toDateString(),
            'sampai' => Carbon::parse($filters['sampai'] ?? $today)->toDateString(),
        ];
    }

    public function rows(string $report, array $filters, bool $paginate = true): Collection|LengthAwarePaginator
    {
        $this->definition($report);
        if ($report === 'laba-rugi') {
            $rows = $this->profitAndLossRows($filters)
                ->map(fn (array $row) => $this->formatRow($report, (object) $row))
                ->values();

            return $paginate ? $this->paginateCollection($rows) : $rows;
        }

        $query = match ($report) {
            'penjualan-tanggal' => $this->salesByDate($filters),
            'penjualan-produk' => $this->salesByProduct($filters),
            'penjualan-pelanggan' => $this->salesByCustomer($filters),
            'pembelian-tanggal' => $this->purchasesByDate($filters),
            'pembelian-supplier' => $this->purchasesBySupplier($filters),
            'stok-saat-ini' => $this->currentStock(),
            'mutasi-stok' => $this->stockMovements($filters),
            'laba-rugi' => throw new \LogicException('Laporan laba rugi menggunakan agregasi per tanggal.'),
        };

        $result = $paginate ? $query->paginate(30)->withQueryString() : $query->get();

        if ($result instanceof LengthAwarePaginator) {
            $result->setCollection($result->getCollection()->map(fn ($row) => $this->formatRow($report, $row)));

            return $result;
        }

        return $result->map(fn ($row) => $this->formatRow($report, $row));
    }

    private function salesByDate(array $filters): Builder
    {
        $query = DB::table('sales')
            ->where('status', 'selesai')
            ->select('tanggal')
            ->selectRaw('COUNT(id) AS transaksi')
            ->selectRaw('SUM(total) AS penjualan')
            ->selectRaw('SUM(diskon) AS diskon')
            ->selectRaw('SUM(pajak) AS pajak');

        return $this->applyDateRange($query, 'tanggal', $filters)
            ->groupBy('tanggal')
            ->orderBy('tanggal');
    }

    private function salesByProduct(array $filters): Builder
    {
        $query = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.status', 'selesai')
            ->when($filters['product_id'] ?? null, fn (Builder $builder, $id) => $builder->where('products.id', $id))
            ->select('products.sku', 'products.nama AS produk')
            ->selectRaw('SUM(sale_items.qty) AS qty')
            ->selectRaw('SUM(sale_items.subtotal) AS penjualan');

        return $this->applyDateRange($query, 'sales.tanggal', $filters)
            ->groupBy('products.id', 'products.sku', 'products.nama')
            ->orderBy('products.nama');
    }

    private function salesByCustomer(array $filters): Builder
    {
        $query = DB::table('sales')
            ->leftJoin('customers', 'customers.id', '=', 'sales.customer_id')
            ->where('sales.status', 'selesai')
            ->when($filters['customer_id'] ?? null, fn (Builder $builder, $id) => $builder->where('sales.customer_id', $id))
            ->selectRaw("COALESCE(customers.nama, 'Pelanggan umum') AS pelanggan")
            ->selectRaw('COUNT(sales.id) AS transaksi')
            ->selectRaw('SUM(sales.total) AS penjualan');

        return $this->applyDateRange($query, 'sales.tanggal', $filters)
            ->groupBy('sales.customer_id', 'customers.nama')
            ->orderBy('pelanggan');
    }

    private function purchasesByDate(array $filters): Builder
    {
        $query = DB::table('purchases')
            ->where('status', 'selesai')
            ->select('tanggal')
            ->selectRaw('COUNT(id) AS transaksi')
            ->selectRaw('SUM(total) AS pembelian')
            ->selectRaw('SUM(diskon) AS diskon');

        return $this->applyDateRange($query, 'tanggal', $filters)
            ->groupBy('tanggal')
            ->orderBy('tanggal');
    }

    private function purchasesBySupplier(array $filters): Builder
    {
        $query = DB::table('purchases')
            ->join('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->where('purchases.status', 'selesai')
            ->when($filters['supplier_id'] ?? null, fn (Builder $builder, $id) => $builder->where('suppliers.id', $id))
            ->select('suppliers.nama AS supplier')
            ->selectRaw('COUNT(purchases.id) AS transaksi')
            ->selectRaw('SUM(purchases.total) AS pembelian');

        return $this->applyDateRange($query, 'purchases.tanggal', $filters)
            ->groupBy('suppliers.id', 'suppliers.nama')
            ->orderBy('suppliers.nama');
    }

    private function currentStock(): Builder
    {
        return DB::table('products')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->whereNull('products.deleted_at')
            ->select('products.sku', 'products.nama AS produk')
            ->selectRaw("COALESCE(categories.nama, '—') AS kategori")
            ->addSelect('products.stok', 'products.harga_beli')
            ->selectRaw('(products.stok * products.harga_beli) AS nilai_persediaan')
            ->orderBy('products.nama');
    }

    private function stockMovements(array $filters): Builder
    {
        $query = DB::table('stock_movements')
            ->join('products', 'products.id', '=', 'stock_movements.product_id')
            ->leftJoin('users', 'users.id', '=', 'stock_movements.user_id')
            ->when($filters['product_id'] ?? null, fn (Builder $builder, $id) => $builder->where('products.id', $id))
            ->select('stock_movements.created_at AS tanggal')
            ->addSelect(
                'products.sku',
                'products.nama AS produk',
                'stock_movements.tipe',
                'stock_movements.qty',
                'stock_movements.stok_sebelum',
                'stock_movements.stok_sesudah',
                'stock_movements.referensi_type AS jenis_referensi',
                'stock_movements.referensi_id',
                'users.name AS pengguna',
            );

        $query->where('stock_movements.created_at', '>=', Carbon::parse($filters['dari'])->startOfDay()->toDateTimeString())
            ->where('stock_movements.created_at', '<=', Carbon::parse($filters['sampai'])->endOfDay()->toDateTimeString());

        return $query->orderByDesc('stock_movements.created_at')->orderByDesc('stock_movements.id');
    }

    private function profitAndLossRows(array $filters): Collection
    {
        $sales = DB::table('sales')
            ->where('status', 'selesai')
            ->select('tanggal')
            ->selectRaw('SUM(subtotal - diskon) AS penjualan');
        $sales = $this->applyDateRange($sales, 'tanggal', $filters)->groupBy('tanggal')->get()->keyBy('tanggal');

        $costs = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.status', 'selesai')
            ->select('sales.tanggal')
            ->selectRaw('(SUM(sale_items.qty * products.harga_beli)) AS hpp');
        $costs = $this->applyDateRange($costs, 'sales.tanggal', $filters)
            ->groupBy('sales.tanggal')
            ->get()
            ->keyBy('tanggal');

        return $sales->map(function ($sale, string $date) use ($costs): array {
            $revenue = (int) $sale->penjualan;
            $cost = (int) ($costs->get($date)->hpp ?? 0);

            return [
                'tanggal' => $date,
                'penjualan' => $revenue,
                'hpp' => $cost,
                'laba' => $revenue - $cost,
            ];
        })->values();
    }

    private function applyDateRange(Builder $query, string $column, array $filters): Builder
    {
        return $query
            ->whereDate($column, '>=', $filters['dari'])
            ->whereDate($column, '<=', $filters['sampai']);
    }

    private function formatRow(string $report, object $row): array
    {
        $data = (array) $row;
        if ($report === 'mutasi-stok') {
            $referenceType = $data['jenis_referensi'] ?? null;
            $referenceName = $referenceType ? class_basename($referenceType) : null;
            $referenceId = $data['referensi_id'] ?? null;
            $data['referensi'] = $referenceName
                ? $referenceName.($referenceId ? " #{$referenceId}" : '')
                : '—';
            $data['pengguna'] = $data['pengguna'] ?? '—';
            $data['tanggal'] = Carbon::parse($data['tanggal'])->format('Y-m-d H:i:s');
        }

        return collect(self::DEFINITIONS[$report]['columns'])
            ->mapWithKeys(fn (array $column) => [
                $column['key'] => $data[$column['key']] ?? null,
            ])
            ->all();
    }

    private function paginateCollection(Collection $rows): LengthAwarePaginator
    {
        $perPage = 30;
        $page = Paginator::resolveCurrentPage();

        return new Paginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'query' => request()->query()],
        );
    }
}
