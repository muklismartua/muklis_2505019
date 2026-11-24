<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Customer;
use App\Models\Receivable;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PenjualanService
{
    public function __construct(
        private readonly NomorTransaksiService $nomorTransaksiService,
        private readonly StockService $stockService,
        private readonly ActivityLogService $activityLogService,
    ) {}

    /**
     * @param  array{
     *     customer_id?: int|string|null,
     *     tanggal: string,
     *     diskon?: int|string,
     *     pajak?: int|string,
     *     pajak_persen?: int|float|string,
     *     bayar: int|string,
     *     metode_pembayaran: string,
     *     catatan?: ?string,
     *     items: list<array{product_id: int|string, qty: int|string, diskon?: int|string}>
     * }  $data
     */
    public function simpan(array $data, User $user): Sale
    {
        return DB::transaction(function () use ($data, $user): Sale {
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
            foreach ($items as $index => $item) {
                $product = $products->get((int) $item['product_id']);
                $quantity = (int) $item['qty'];
                $discount = (int) ($item['diskon'] ?? 0);
                $gross = $this->multiply($product->harga_jual, $quantity);

                if ($discount > $gross) {
                    throw ValidationException::withMessages([
                        "items.{$index}.diskon" => "Diskon item {$product->nama} tidak boleh melebihi subtotal item.",
                    ]);
                }

                $lineSubtotal = $gross - $discount;
                $subtotal = $this->add($subtotal, $lineSubtotal, 'Subtotal penjualan melebihi batas yang dapat dihitung.');
            }

            $discount = (int) ($data['diskon'] ?? 0);
            $payment = (int) $data['bayar'];
            $taxRate = array_key_exists('pajak_persen', $data) ? (float) $data['pajak_persen'] : null;
            $paymentMethod = $data['metode_pembayaran'];

            if ($paymentMethod === Sale::PAYMENT_TEMPO && empty($data['customer_id'])) {
                throw ValidationException::withMessages([
                    'customer_id' => 'Pelanggan wajib dipilih untuk penjualan dengan pembayaran tempo.',
                ]);
            }

            if (
                ! empty($data['customer_id'])
                && ! Customer::query()->whereKey($data['customer_id'])->where('aktif', true)->exists()
            ) {
                throw ValidationException::withMessages([
                    'customer_id' => 'Pelanggan yang dipilih tidak ditemukan.',
                ]);
            }

            if ($discount < 0) {
                throw ValidationException::withMessages([
                    'diskon' => 'Diskon tidak boleh bernilai negatif.',
                ]);
            }

            if ($payment < 0) {
                throw ValidationException::withMessages([
                    'bayar' => 'Jumlah bayar tidak boleh bernilai negatif.',
                ]);
            }

            if ($taxRate !== null && ($taxRate < 0 || $taxRate > 100)) {
                throw ValidationException::withMessages([
                    'pajak_persen' => 'Persentase pajak harus antara 0 sampai 100.',
                ]);
            }

            if ($discount > $subtotal) {
                throw ValidationException::withMessages([
                    'diskon' => 'Diskon tidak boleh melebihi subtotal penjualan.',
                ]);
            }

            $totalBeforeTax = $subtotal - $discount;
            $tax = $taxRate === null
                ? (int) ($data['pajak'] ?? 0)
                : $this->hitungPajak($totalBeforeTax, $taxRate);

            if ($tax < 0) {
                throw ValidationException::withMessages([
                    'pajak' => 'Pajak tidak boleh bernilai negatif.',
                ]);
            }

            $total = $this->add($totalBeforeTax, $tax, 'Total penjualan melebihi batas yang dapat dihitung.');
            if ($paymentMethod !== Sale::PAYMENT_TEMPO && $payment < $total) {
                throw ValidationException::withMessages([
                    'bayar' => 'Jumlah bayar tidak mencukupi total penjualan.',
                ]);
            }

            if ($payment > PHP_INT_MAX - $total) {
                throw ValidationException::withMessages([
                    'bayar' => 'Jumlah bayar melebihi batas yang dapat dihitung.',
                ]);
            }

            $sale = Sale::query()->create([
                'nomor' => $this->nomorTransaksiService->nomorPenjualan(),
                'customer_id' => $data['customer_id'] ?? null,
                'user_id' => $user->id,
                'tanggal' => $data['tanggal'],
                'subtotal' => $subtotal,
                'diskon' => $discount,
                'pajak' => $tax,
                'total' => $total,
                'bayar' => $payment,
                'kembalian' => max($payment - $total, 0),
                'metode_pembayaran' => $paymentMethod,
                'status' => Sale::STATUS_COMPLETED,
                'catatan' => $data['catatan'] ?? null,
            ]);

            foreach ($items as $line) {
                $product = $products->get((int) $line['product_id']);
                $quantity = (int) $line['qty'];
                $itemDiscount = (int) ($line['diskon'] ?? 0);
                $unitPrice = $product->harga_jual;
                $lineSubtotal = $this->multiply($unitPrice, $quantity) - $itemDiscount;

                $sale->items()->create([
                    'product_id' => $product->id,
                    'qty' => $quantity,
                    'harga_jual' => $unitPrice,
                    'diskon' => $itemDiscount,
                    'subtotal' => $lineSubtotal,
                ]);

                $this->stockService->kurangi(
                    $product,
                    $quantity,
                    $sale,
                    "Penjualan {$sale->nomor}",
                    $user,
                );
            }

            if ($paymentMethod === Sale::PAYMENT_TEMPO) {
                $remaining = $total - min($payment, $total);
                Receivable::query()->create([
                    'sale_id' => $sale->id,
                    'customer_id' => $sale->customer_id,
                    'total' => $total,
                    'terbayar' => min($payment, $total),
                    'sisa' => $remaining,
                    'status' => match (true) {
                        $remaining === 0 => Receivable::STATUS_PAID,
                        $payment > 0 => Receivable::STATUS_PARTIAL,
                        default => Receivable::STATUS_UNPAID,
                    },
                ]);
            }

            $this->activityLogService->catat(
                'penjualan.dibuat',
                "Penjualan {$sale->nomor} berhasil dibuat.",
                $sale,
                $user,
            );

            return $sale->load(['items.product', 'customer', 'user', 'receivable']);
        });
    }

    public function batalkan(Sale $sale, User $user): Sale
    {
        return DB::transaction(function () use ($sale, $user): Sale {
            $sale = Sale::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($sale->id);

            if ($sale->status !== Sale::STATUS_COMPLETED) {
                throw ValidationException::withMessages([
                    'status' => 'Penjualan ini sudah dibatalkan.',
                ]);
            }

            $itemsByProduct = $sale->items->groupBy('product_id');
            $products = Product::query()
                ->withTrashed()
                ->whereIn('id', $itemsByProduct->keys())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== $itemsByProduct->count()) {
                throw ValidationException::withMessages([
                    'sale' => 'Produk pada penjualan tidak lagi tersedia untuk pembatalan.',
                ]);
            }

            foreach ($sale->items as $item) {
                $this->stockService->tambah(
                    $products->get($item->product_id),
                    $item->qty,
                    $sale,
                    "Pembatalan penjualan {$sale->nomor}",
                    $user,
                );
            }

            $sale->update(['status' => Sale::STATUS_CANCELLED]);
            $sale->receivable?->update([
                'sisa' => 0,
                'status' => Receivable::STATUS_CANCELLED,
            ]);
            $this->activityLogService->catat(
                'penjualan.dibatalkan',
                "Penjualan {$sale->nomor} dibatalkan.",
                $sale,
                $user,
            );

            return $sale->load(['items.product', 'customer', 'user', 'receivable']);
        });
    }

    private function hitungPajak(int $dasarPajak, float $persentase): int
    {
        $basisPoin = (int) round($persentase * 100);
        $bagianUtuh = intdiv($dasarPajak, 10000) * $basisPoin;
        $sisa = ($dasarPajak % 10000) * $basisPoin;

        return $bagianUtuh + intdiv($sisa + 5000, 10000);
    }

    private function multiply(int $price, int $quantity): int
    {
        if ($price < 0 || $quantity < 1 || ($price > 0 && $quantity > intdiv(PHP_INT_MAX, $price))) {
            throw ValidationException::withMessages([
                'items' => 'Harga atau jumlah item melebihi batas yang dapat dihitung.',
            ]);
        }

        return $price * $quantity;
    }

    private function add(int $total, int $amount, string $message): int
    {
        if ($amount < 0 || $amount > PHP_INT_MAX - $total) {
            throw ValidationException::withMessages(['items' => $message]);
        }

        return $total + $amount;
    }
}
