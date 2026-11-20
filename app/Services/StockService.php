<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionItem;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class StockService
{
    public function recordTransactionMovement(
        InventoryTransaction $transaction,
        InventoryTransactionItem $item,
    ): StockMovement {
        $product = Product::query()->lockForUpdate()->findOrFail($item->product_id);
        $stockBefore = $product->stock;

        [$stockAfter, $movementType] = match ($transaction->type) {
            InventoryTransaction::TYPE_SALE => $this->decreaseStock($product, $stockBefore, $item->quantity),
            InventoryTransaction::TYPE_PURCHASE => [$stockBefore + $item->quantity, 'masuk'],
            default => throw new InvalidArgumentException('Jenis transaksi stok tidak valid.'),
        };

        $product->stock = $stockAfter;
        $product->save();

        return StockMovement::query()->create([
            'product_id' => $product->id,
            'inventory_transaction_id' => $transaction->id,
            'inventory_transaction_item_id' => $item->id,
            'type' => $movementType,
            'quantity' => $item->quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
        ]);
    }

    /**
     * @return array{int, string}
     */
    private function decreaseStock(Product $product, int $stockBefore, int $quantity): array
    {
        if ($stockBefore < $quantity) {
            throw ValidationException::withMessages([
                'items' => "Stok {$product->name} tidak mencukupi. Stok tersedia: {$stockBefore}.",
            ]);
        }

        return [$stockBefore - $quantity, 'keluar'];
    }
}
