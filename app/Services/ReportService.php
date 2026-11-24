<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
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
                ['key' => 'penjualan', 'label' => 'Total Penjualan', 'type' => 'money'],
                ['key' => 'diterima', 'label' => 'Total Diterima (kas masuk)', 'type' => 'money'],
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
                ['key' => 'penjualan', 'label' => 'Total Penjualan', 'type' => 'money'],
                ['key' => 'diterima', 'label' => 'Total Diterima (kas masuk)', 'type' => 'money'],
            ],
        ],
        'penjualan-pelanggan' => [
            'title' => 'Laporan Penjualan per Pelanggan',
            'description' => 'Ringkasan nilai transaksi selesai menurut pelanggan.',
            'filters' => ['periode' => true, 'pelanggan' => true],
            'columns' => [
                ['key' => 'pelanggan', 'label' => 'Pelanggan', 'type' => 'text'],
                ['key' => 'transaksi', 'label' => 'Transaksi', 'type' => 'number'],
                ['key' => 'penjualan', 'label' => 'Total Penjualan', 'type' => 'money'],
                ['key' => 'diterima', 'label' => 'Total Diterima (kas masuk)', 'type' => 'money'],
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
        'piutang-pelanggan' => [
            'title' => 'Laporan Piutang',
            'description' => 'Daftar piutang menurut pelanggan, status, dan rentang tanggal jatuh tempo.',
            'filters' => ['pelanggan' => true, 'status' => true, 'rentang_jatuh_tempo' => true],
            'columns' => [
                ['key' => 'nomor', 'label' => 'Nomor Penjualan', 'type' => 'text'],
                ['key' => 'pelanggan', 'label' => 'Pelanggan', 'type' => 'text'],
                ['key' => 'tanggal', 'label' => 'Tanggal Penjualan', 'type' => 'date'],
                ['key' => 'jatuh_tempo', 'label' => 'Jatuh Tempo', 'type' => 'date'],
                ['key' => 'status', 'label' => 'Status Piutang', 'type' => 'text'],
                ['key' => 'total', 'label' => 'Total Tagihan', 'type' => 'money'],
                ['key' => 'terbayar', 'label' => 'Terbayar', 'type' => 'money'],
                ['key' => 'sisa', 'label' => 'Sisa', 'type' => 'money'],
            ],
        ],
        'hutang-supplier' => [
            'title' => 'Laporan Hutang Supplier',
            'description' => 'Daftar hutang pembelian menurut supplier, status, dan rentang tanggal jatuh tempo.',
            'filters' => ['supplier' => true, 'status' => true, 'rentang_jatuh_tempo' => true],
            'columns' => [
                ['key' => 'nomor', 'label' => 'Nomor Pembelian', 'type' => 'text'],
                ['key' => 'supplier', 'label' => 'Supplier', 'type' => 'text'],
                ['key' => 'tanggal', 'label' => 'Tanggal Pembelian', 'type' => 'date'],
                ['key' => 'jatuh_tempo', 'label' => 'Jatuh Tempo', 'type' => 'date'],
                ['key' => 'status', 'label' => 'Status Hutang', 'type' => 'text'],
                ['key' => 'total', 'label' => 'Total Tagihan', 'type' => 'money'],
                ['key' => 'terbayar', 'label' => 'Terbayar', 'type' => 'money'],
                ['key' => 'sisa', 'label' => 'Sisa', 'type' => 'money'],
            ],
        ],
        'piutang-aging' => [
            'title' => 'Umur Piutang (Aging)',
            'description' => 'Sisa piutang belum lunas dikelompokkan berdasarkan jumlah hari setelah jatuh tempo.',
            'filters' => ['pelanggan' => true],
            'columns' => [
                ['key' => 'pelanggan', 'label' => 'Pelanggan', 'type' => 'text'],
                ['key' => 'hari_0_30', 'label' => '0–30 Hari', 'type' => 'money'],
                ['key' => 'hari_31_60', 'label' => '31–60 Hari', 'type' => 'money'],
                ['key' => 'hari_61_90', 'label' => '61–90 Hari', 'type' => 'money'],
                ['key' => 'hari_lebih_90', 'label' => '>90 Hari', 'type' => 'money'],
                ['key' => 'total', 'label' => 'Total Piutang', 'type' => 'money'],
            ],
        ],
        'riwayat-pembayaran-piutang' => [
            'title' => 'Riwayat Pembayaran Piutang',
            'description' => 'Pembayaran dan cicilan piutang yang diterima pada periode yang dipilih.',
            'filters' => ['periode' => true, 'pelanggan' => true],
            'columns' => [
                ['key' => 'tanggal', 'label' => 'Tanggal', 'type' => 'date'],
                ['key' => 'nomor_pembayaran', 'label' => 'Nomor Pembayaran', 'type' => 'text'],
                ['key' => 'nomor_penjualan', 'label' => 'Nomor Penjualan', 'type' => 'text'],
                ['key' => 'pelanggan', 'label' => 'Pelanggan', 'type' => 'text'],
                ['key' => 'jumlah', 'label' => 'Jumlah Diterima', 'type' => 'money'],
                ['key' => 'metode', 'label' => 'Metode', 'type' => 'text'],
                ['key' => 'pengguna', 'label' => 'Dicatat oleh', 'type' => 'text'],
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
            'jatuh_dari' => isset($filters['jatuh_dari']) ? Carbon::parse($filters['jatuh_dari'])->toDateString() : null,
            'jatuh_sampai' => isset($filters['jatuh_sampai']) ? Carbon::parse($filters['jatuh_sampai'])->toDateString() : null,
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
        if (in_array($report, ['penjualan-tanggal', 'penjualan-produk', 'penjualan-pelanggan', 'piutang-aging'], true)) {
            $rows = match ($report) {
                'penjualan-tanggal' => $this->salesByDate($filters),
                'penjualan-produk' => $this->salesByProduct($filters),
                'penjualan-pelanggan' => $this->salesByCustomer($filters),
                'piutang-aging' => $this->receivableAgingRows($filters),
            };

            return $paginate ? $this->paginateCollection($rows) : $rows;
        }

        $query = match ($report) {
            'pembelian-tanggal' => $this->purchasesByDate($filters),
            'pembelian-supplier' => $this->purchasesBySupplier($filters),
            'stok-saat-ini' => $this->currentStock(),
            'mutasi-stok' => $this->stockMovements($filters),
            'piutang-pelanggan' => $this->receivables($filters),
            'hutang-supplier' => $this->supplierDebts($filters),
            'riwayat-pembayaran-piutang' => $this->receivablePayments($filters),
            'laba-rugi' => throw new \LogicException('Laporan laba rugi menggunakan agregasi per tanggal.'),
            'penjualan-tanggal', 'penjualan-produk', 'penjualan-pelanggan', 'piutang-aging' => throw new \LogicException('Laporan ini menggunakan agregasi khusus.'),
        };

        $result = $paginate ? $query->paginate(30)->withQueryString() : $query->get();

        if ($result instanceof LengthAwarePaginator) {
            $result->setCollection($result->getCollection()->map(fn ($row) => $this->formatRow($report, $row)));

            return $result;
        }

        return $result->map(fn ($row) => $this->formatRow($report, $row));
    }

    private function salesByDate(array $filters): Collection
    {
        $sales = DB::table('sales')
            ->where('status', 'selesai')
            ->select('tanggal')
            ->selectRaw('COUNT(id) AS transaksi')
            ->selectRaw('SUM(total) AS penjualan')
            ->selectRaw('SUM(diskon) AS diskon')
            ->selectRaw('SUM(pajak) AS pajak');
        $sales = $this->applyDateRange($sales, 'tanggal', $filters)
            ->groupBy('tanggal')
            ->get()
            ->keyBy('tanggal');
        $receipts = $this->cashReceiptsByDate($filters);

        return $sales->keys()
            ->merge($receipts->keys())
            ->unique()
            ->sort()
            ->map(fn (string $date): array => [
                'tanggal' => $date,
                'transaksi' => (int) ($sales->get($date)->transaksi ?? 0),
                'penjualan' => (int) ($sales->get($date)->penjualan ?? 0),
                'diterima' => (int) ($receipts->get($date) ?? 0),
                'diskon' => (int) ($sales->get($date)->diskon ?? 0),
                'pajak' => (int) ($sales->get($date)->pajak ?? 0),
            ])
            ->values();
    }

    private function salesByProduct(array $filters): Collection
    {
        $items = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.status', 'selesai')
            ->select(
                'sales.id as sale_id',
                'sales.bayar',
                'sales.total as sale_total',
                'products.id as product_id',
                'products.sku',
                'products.nama as produk',
                'sale_items.qty',
                'sale_items.subtotal',
            );
        $items = $this->applyDateRange($items, 'sales.tanggal', $filters)
            ->orderBy('sales.id')
            ->orderBy('products.id')
            ->get();
        $saleIds = $items->pluck('sale_id')->unique()->values();
        if ($saleIds->isEmpty()) {
            return collect();
        }

        $paymentsBySale = DB::table('piutang_payments')
            ->join('piutangs', 'piutangs.id', '=', 'piutang_payments.piutang_id')
            ->join('sales', 'sales.id', '=', 'piutangs.sale_id')
            ->where('sales.status', 'selesai')
            ->whereIn('sales.id', $saleIds)
            ->select('sales.id as sale_id')
            ->selectRaw('SUM(piutang_payments.jumlah) AS diterima');
        $paymentsBySale = $this->applyDateRange($paymentsBySale, 'piutang_payments.tanggal', $filters)
            ->groupBy('sales.id')
            ->get()
            ->keyBy('sale_id');
        $rows = [];

        foreach ($items->groupBy('sale_id') as $saleItems) {
            $firstItem = $saleItems->first();
            $saleLines = $saleItems->values();
            $itemValueTotal = (int) $saleLines->sum('subtotal');
            $lastValueIndex = $saleLines
                ->filter(fn ($item) => (int) $item->subtotal > 0)
                ->keys()
                ->last();
            $cashReceived = min(
                (int) $firstItem->sale_total,
                min((int) $firstItem->bayar, (int) $firstItem->sale_total)
                    + (int) ($paymentsBySale->get($firstItem->sale_id)->diterima ?? 0),
            );
            $cashRemaining = $cashReceived;

            foreach ($saleLines as $index => $item) {
                $received = $itemValueTotal <= 0
                    ? ($index === $saleLines->count() - 1 ? $cashRemaining : 0)
                    : ((int) $item->subtotal <= 0
                        ? 0
                        : ($index === $lastValueIndex
                            ? $cashRemaining
                            : min($cashRemaining, (int) round($cashReceived * ((int) $item->subtotal / $itemValueTotal)))));
                $cashRemaining -= $received;

                if (isset($filters['product_id']) && (int) $filters['product_id'] !== (int) $item->product_id) {
                    continue;
                }

                $rows[$item->product_id] ??= [
                    'sku' => $item->sku,
                    'produk' => $item->produk,
                    'qty' => 0,
                    'penjualan' => 0,
                    'diterima' => 0,
                ];
                $rows[$item->product_id]['qty'] += (int) $item->qty;
                $rows[$item->product_id]['penjualan'] += (int) $item->subtotal;
                $rows[$item->product_id]['diterima'] += $received;
            }
        }

        return collect($rows)->sortBy('produk')->values();
    }

    private function salesByCustomer(array $filters): Collection
    {
        $sales = DB::table('sales')
            ->leftJoin('customers', 'customers.id', '=', 'sales.customer_id')
            ->where('sales.status', 'selesai')
            ->when($filters['customer_id'] ?? null, fn (Builder $builder, $id) => $builder->where('sales.customer_id', $id))
            ->select('sales.customer_id')
            ->selectRaw("COALESCE(customers.nama, 'Pelanggan umum') AS pelanggan")
            ->selectRaw('COUNT(sales.id) AS transaksi')
            ->selectRaw('SUM(sales.total) AS penjualan')
            ->selectRaw('SUM(CASE WHEN sales.bayar < sales.total THEN sales.bayar ELSE sales.total END) AS diterima');
        $sales = $this->applyDateRange($sales, 'sales.tanggal', $filters)
            ->groupBy('sales.customer_id', 'customers.nama')
            ->get();

        $rows = $sales->mapWithKeys(fn ($row): array => [
            (int) ($row->customer_id ?? 0) => [
                'pelanggan' => $row->pelanggan,
                'transaksi' => (int) $row->transaksi,
                'penjualan' => (int) $row->penjualan,
                'diterima' => (int) $row->diterima,
            ],
        ]);
        $payments = DB::table('piutang_payments')
            ->join('piutangs', 'piutangs.id', '=', 'piutang_payments.piutang_id')
            ->join('sales', 'sales.id', '=', 'piutangs.sale_id')
            ->leftJoin('customers', 'customers.id', '=', 'sales.customer_id')
            ->where('sales.status', 'selesai')
            ->when($filters['customer_id'] ?? null, fn (Builder $builder, $id) => $builder->where('sales.customer_id', $id))
            ->select('sales.customer_id')
            ->selectRaw("COALESCE(customers.nama, 'Pelanggan umum') AS pelanggan")
            ->selectRaw('SUM(piutang_payments.jumlah) AS diterima');
        $payments = $this->applyDateRange($payments, 'piutang_payments.tanggal', $filters)
            ->groupBy('sales.customer_id', 'customers.nama')
            ->get();

        foreach ($payments as $payment) {
            $key = (int) ($payment->customer_id ?? 0);
            $row = $rows->get($key, [
                'pelanggan' => $payment->pelanggan,
                'transaksi' => 0,
                'penjualan' => 0,
                'diterima' => 0,
            ]);
            $row['diterima'] += (int) $payment->diterima;
            $rows->put($key, $row);
        }

        return $rows->sortBy('pelanggan')->values();
    }

    private function cashReceiptsByDate(array $filters): Collection
    {
        $salesReceipts = DB::table('sales')
            ->where('status', 'selesai')
            ->select('tanggal')
            ->selectRaw('SUM(CASE WHEN bayar < total THEN bayar ELSE total END) AS diterima');
        $salesReceipts = $this->applyDateRange($salesReceipts, 'tanggal', $filters)
            ->groupBy('tanggal')
            ->get()
            ->mapWithKeys(fn ($row): array => [$row->tanggal => (int) $row->diterima]);

        $paymentReceipts = DB::table('piutang_payments')
            ->join('piutangs', 'piutangs.id', '=', 'piutang_payments.piutang_id')
            ->join('sales', 'sales.id', '=', 'piutangs.sale_id')
            ->where('sales.status', 'selesai')
            ->select('piutang_payments.tanggal')
            ->selectRaw('SUM(piutang_payments.jumlah) AS diterima');
        $paymentReceipts = $this->applyDateRange($paymentReceipts, 'piutang_payments.tanggal', $filters)
            ->groupBy('piutang_payments.tanggal')
            ->get()
            ->mapWithKeys(fn ($row): array => [$row->tanggal => (int) $row->diterima]);

        $receipts = $salesReceipts;
        foreach ($paymentReceipts as $date => $amount) {
            $receipts->put($date, (int) $receipts->get($date, 0) + $amount);
        }

        return $receipts;
    }

    private function receivables(array $filters): Builder
    {
        return DB::table('piutangs')
            ->join('customers', 'customers.id', '=', 'piutangs.customer_id')
            ->join('sales', 'sales.id', '=', 'piutangs.sale_id')
            ->when($filters['customer_id'] ?? null, fn (Builder $query, $id) => $query->where('piutangs.customer_id', $id))
            ->when($filters['status'] ?? null, fn (Builder $query, $status) => $query->where('piutangs.status', $status))
            ->when($filters['jatuh_dari'] ?? null, fn (Builder $query, $date) => $query->whereDate('piutangs.jatuh_tempo', '>=', $date))
            ->when($filters['jatuh_sampai'] ?? null, fn (Builder $query, $date) => $query->whereDate('piutangs.jatuh_tempo', '<=', $date))
            ->select(
                'sales.nomor',
                'customers.nama as pelanggan',
                'sales.tanggal',
                'piutangs.jatuh_tempo',
                'piutangs.status',
                'piutangs.total',
                'piutangs.terbayar',
                'piutangs.sisa',
            )
            ->orderBy('piutangs.jatuh_tempo')
            ->orderBy('sales.nomor');
    }

    private function supplierDebts(array $filters): Builder
    {
        return DB::table('hutang_suppliers')
            ->join('suppliers', 'suppliers.id', '=', 'hutang_suppliers.supplier_id')
            ->join('purchases', 'purchases.id', '=', 'hutang_suppliers.purchase_id')
            ->when($filters['supplier_id'] ?? null, fn (Builder $query, $id) => $query->where('hutang_suppliers.supplier_id', $id))
            ->when($filters['status'] ?? null, fn (Builder $query, $status) => $query->where('hutang_suppliers.status', $status))
            ->when($filters['jatuh_dari'] ?? null, fn (Builder $query, $date) => $query->whereDate('hutang_suppliers.jatuh_tempo', '>=', $date))
            ->when($filters['jatuh_sampai'] ?? null, fn (Builder $query, $date) => $query->whereDate('hutang_suppliers.jatuh_tempo', '<=', $date))
            ->select(
                'purchases.nomor',
                'suppliers.nama as supplier',
                'purchases.tanggal',
                'hutang_suppliers.jatuh_tempo',
                'hutang_suppliers.status',
                'hutang_suppliers.total',
                'hutang_suppliers.terbayar',
                'hutang_suppliers.sisa',
            )
            ->orderBy('hutang_suppliers.jatuh_tempo')
            ->orderBy('purchases.nomor');
    }

    private function receivableAgingRows(array $filters): Collection
    {
        $today = Carbon::today();
        $balances = DB::table('piutangs')
            ->join('customers', 'customers.id', '=', 'piutangs.customer_id')
            ->where('piutangs.status', 'belum_lunas')
            ->where('piutangs.sisa', '>', 0)
            ->whereNotNull('piutangs.jatuh_tempo')
            ->when($filters['customer_id'] ?? null, fn (Builder $query, $id) => $query->where('customers.id', $id))
            ->get([
                'customers.id as customer_id',
                'customers.nama as pelanggan',
                'piutangs.jatuh_tempo',
                'piutangs.sisa',
            ]);
        $rows = [];

        foreach ($balances as $balance) {
            $dueDate = Carbon::parse($balance->jatuh_tempo)->startOfDay();
            if ($dueDate->gt($today)) {
                continue;
            }

            $daysLate = (int) $dueDate->diffInDays($today);
            $key = (int) $balance->customer_id;
            $rows[$key] ??= [
                'pelanggan' => $balance->pelanggan,
                'hari_0_30' => 0,
                'hari_31_60' => 0,
                'hari_61_90' => 0,
                'hari_lebih_90' => 0,
                'total' => 0,
            ];
            $bucket = match (true) {
                $daysLate <= 30 => 'hari_0_30',
                $daysLate <= 60 => 'hari_31_60',
                $daysLate <= 90 => 'hari_61_90',
                default => 'hari_lebih_90',
            };
            $rows[$key][$bucket] += (int) $balance->sisa;
            $rows[$key]['total'] += (int) $balance->sisa;
        }

        return collect($rows)->sortByDesc('total')->values();
    }

    private function receivablePayments(array $filters): Builder
    {
        $query = DB::table('piutang_payments')
            ->join('piutangs', 'piutangs.id', '=', 'piutang_payments.piutang_id')
            ->join('sales', 'sales.id', '=', 'piutangs.sale_id')
            ->join('customers', 'customers.id', '=', 'piutangs.customer_id')
            ->leftJoin('users', 'users.id', '=', 'piutang_payments.user_id')
            ->when($filters['customer_id'] ?? null, fn (Builder $builder, $id) => $builder->where('piutangs.customer_id', $id))
            ->select(
                'piutang_payments.tanggal',
                'piutang_payments.nomor as nomor_pembayaran',
                'sales.nomor as nomor_penjualan',
                'customers.nama as pelanggan',
                'piutang_payments.jumlah',
                'piutang_payments.metode',
                'users.name as pengguna',
            );

        return $this->applyDateRange($query, 'piutang_payments.tanggal', $filters)
            ->orderByDesc('piutang_payments.tanggal')
            ->orderByDesc('piutang_payments.id');
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
