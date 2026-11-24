<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PembelianService
{
    public function __construct(
        private readonly NomorTransaksiService $nomorTransaksiService,
        private readonly StockService $stockService,
        private readonly ActivityLogService $activityLogService,
        private readonly HutangSupplierService $hutangSupplierService,
    ) {}

    /**
     * @param  array{
     *     supplier_id: int|string,
     *     tanggal: string,
     *     diskon?: int|string,
     *     metode_pembayaran: string,
     *     bayar?: int|string,
     *     metode_dp?: string,
     *     jatuh_tempo?: ?string,
     *     catatan?: ?string,
     *     perbarui_harga_beli?: bool|string|int,
     *     items: list<array{product_id: int|string, qty: int|string, harga_beli: int|string}>
     * }  $data
     */
    public function simpan(array $data, User $user): Purchase
    {
        return DB::transaction(function () use ($data, $user): Purchase {
            $items = collect($data['items']);
            $productIds = $items->pluck('product_id')->map(fn ($id) => (int) $id);

            if ($productIds->duplicates()->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Produk yang sama tidak boleh ditambahkan lebih dari satu kali.',
                ]);
            }

            $products = Product::query()
                ->whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== $productIds->unique()->count()) {
                throw ValidationException::withMessages([
                    'items' => 'Salah satu produk tidak lagi tersedia.',
                ]);
            }

            foreach ($products as $product) {
                if (! $product->aktif) {
                    throw ValidationException::withMessages([
                        'items' => "Produk {$product->nama} sedang tidak aktif.",
                    ]);
                }
            }

            $subtotal = 0;
            foreach ($items as $item) {
                $lineSubtotal = $this->lineSubtotal((int) $item['harga_beli'], (int) $item['qty']);
                if ($lineSubtotal > PHP_INT_MAX - $subtotal) {
                    throw ValidationException::withMessages([
                        'items' => 'Subtotal pembelian melebihi batas yang dapat dihitung.',
                    ]);
                }
                $subtotal += $lineSubtotal;
            }
            $diskon = (int) ($data['diskon'] ?? 0);

            if ($diskon > $subtotal) {
                throw ValidationException::withMessages([
                    'diskon' => 'Diskon tidak boleh melebihi subtotal pembelian.',
                ]);
            }

            $total = $subtotal - $diskon;
            $isCredit = $data['metode_pembayaran'] === 'tempo';
            $paidAmount = $isCredit ? (int) ($data['bayar'] ?? 0) : $total;
            if ($paidAmount < 0 || $paidAmount > $total) {
                throw ValidationException::withMessages([
                    'bayar' => 'Uang muka tidak boleh melebihi total pembelian.',
                ]);
            }
            $remaining = $total - $paidAmount;

            $purchase = Purchase::query()->create([
                'nomor' => $this->nomorTransaksiService->nomorPembelian(),
                'supplier_id' => $data['supplier_id'],
                'user_id' => $user->id,
                'tanggal' => $data['tanggal'],
                'subtotal' => $subtotal,
                'diskon' => $diskon,
                'total' => $total,
                'metode_pembayaran' => $data['metode_pembayaran'],
                'status_pembayaran' => $remaining > 0 ? 'belum_lunas' : 'lunas',
                'jatuh_tempo' => $remaining > 0 ? ($data['jatuh_tempo'] ?? null) : null,
                'total_dibayar' => $paidAmount,
                'sisa' => $remaining,
                'status' => Purchase::STATUS_COMPLETED,
                'catatan' => $data['catatan'] ?? null,
            ]);

            $updatePurchasePrice = filter_var(
                $data['perbarui_harga_beli'] ?? false,
                FILTER_VALIDATE_BOOLEAN,
            );

            foreach ($items as $line) {
                $product = $products->get((int) $line['product_id']);
                $quantity = (int) $line['qty'];
                $unitPrice = (int) $line['harga_beli'];
                $lineSubtotal = $this->lineSubtotal($unitPrice, $quantity);

                $purchase->items()->create([
                    'product_id' => $product->id,
                    'qty' => $quantity,
                    'harga_beli' => $unitPrice,
                    'subtotal' => $lineSubtotal,
                ]);

                $this->stockService->tambah(
                    $product,
                    $quantity,
                    $purchase,
                    "Pembelian {$purchase->nomor}",
                    $user,
                );

                if ($updatePurchasePrice && $product->harga_beli !== $unitPrice) {
                    $product->harga_beli = $unitPrice;
                    $product->save();
                }
            }

            if ($data['metode_pembayaran'] === 'tempo') {
                $this->hutangSupplierService->buatDariPembelian(
                    $purchase,
                    (int) ($data['bayar'] ?? 0),
                    $data['jatuh_tempo'] ?? null,
                    $user,
                    $data['metode_dp'] ?? 'tunai',
                );
            }

            $this->activityLogService->catat(
                'pembelian.dibuat',
                "Pembelian {$purchase->nomor} berhasil dibuat.",
                $purchase,
                $user,
            );

            return $purchase->refresh()->load(['items.product', 'supplier', 'user', 'hutangSupplier']);
        });
    }

    public function batalkan(Purchase $purchase, User $user): Purchase
    {
        return DB::transaction(function () use ($purchase, $user): Purchase {
            $purchase = Purchase::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($purchase->id);

            if ($purchase->status !== Purchase::STATUS_COMPLETED) {
                throw ValidationException::withMessages([
                    'status' => 'Pembelian ini sudah dibatalkan.',
                ]);
            }

            $itemsByProduct = $purchase->items->groupBy('product_id');
            $products = Product::query()
                ->withTrashed()
                ->whereIn('id', $itemsByProduct->keys())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== $itemsByProduct->count()) {
                throw ValidationException::withMessages([
                    'purchase' => 'Produk pada pembelian tidak lagi tersedia untuk pembatalan.',
                ]);
            }

            foreach ($itemsByProduct as $productId => $purchaseItems) {
                $requiredStock = $purchaseItems->sum('qty');
                $product = $products->get((int) $productId);

                if ($product->stok < $requiredStock) {
                    throw ValidationException::withMessages([
                        'purchase' => "Pembelian tidak dapat dibatalkan. Stok {$product->nama} tersisa {$product->stok}, sedangkan {$requiredStock} harus dikurangi.",
                    ]);
                }
            }

            $this->hutangSupplierService->batalkan($purchase, $user);

            foreach ($purchase->items as $item) {
                $product = $products->get($item->product_id);
                $this->stockService->kurangi(
                    $product,
                    $item->qty,
                    $purchase,
                    "Pembatalan pembelian {$purchase->nomor}",
                    $user,
                );
            }

            $purchase->update(['status' => Purchase::STATUS_CANCELLED]);
            $this->activityLogService->catat(
                'pembelian.dibatalkan',
                "Pembelian {$purchase->nomor} dibatalkan.",
                $purchase,
                $user,
            );

            return $purchase->load(['items.product', 'supplier', 'user']);
        });
    }

    private function lineSubtotal(int $unitPrice, int $quantity): int
    {
        if ($unitPrice < 0 || $quantity < 1 || ($unitPrice > 0 && $quantity > intdiv(PHP_INT_MAX, $unitPrice))) {
            throw ValidationException::withMessages([
                'items' => 'Harga atau jumlah item melebihi batas yang dapat dihitung.',
            ]);
        }

        return $unitPrice * $quantity;
    }
}
