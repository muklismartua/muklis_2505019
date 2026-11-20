<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InventoryTransactionService
{
    public function __construct(private readonly StockService $stockService) {}

    /**
     * @param  array{type: string, transaction_date: string, notes?: ?string, items: list<array{product_id: int|string, quantity: int|string}>}  $data
     */
    public function create(array $data, ?User $user = null): InventoryTransaction
    {
        return DB::transaction(function () use ($data, $user): InventoryTransaction {
            $productIds = collect($data['items'])->pluck('product_id')->map(fn ($id) => (int) $id);
            $products = Product::query()
                ->whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== $productIds->unique()->count()) {
                throw ValidationException::withMessages([
                    'items' => 'Salah satu produk tidak lagi tersedia.',
                ]);
            }

            foreach ($products as $product) {
                if (! $product->is_active) {
                    throw ValidationException::withMessages([
                        'items' => "Produk {$product->name} sedang tidak aktif.",
                    ]);
                }
            }

            $transaction = InventoryTransaction::query()->create([
                'transaction_number' => $this->generateTransactionNumber($data['type']),
                'type' => $data['type'],
                'transaction_date' => $data['transaction_date'],
                'total' => 0,
                'notes' => $data['notes'] ?? null,
                'user_id' => $user?->id,
            ]);

            $total = 0;

            foreach ($data['items'] as $line) {
                $product = $products->get((int) $line['product_id']);
                $quantity = (int) $line['quantity'];
                $unitPrice = $data['type'] === InventoryTransaction::TYPE_SALE
                    ? $product->selling_price
                    : $product->purchase_price;
                $subtotal = $unitPrice * $quantity;

                $item = $transaction->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);

                $this->stockService->recordTransactionMovement($transaction, $item);
                $total += $subtotal;
            }

            $transaction->update(['total' => $total]);

            return $transaction->load('items');
        });
    }

    private function generateTransactionNumber(string $type): string
    {
        $prefix = $type === InventoryTransaction::TYPE_SALE ? 'JUAL' : 'BELI';

        return $prefix.'-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
    }
}
