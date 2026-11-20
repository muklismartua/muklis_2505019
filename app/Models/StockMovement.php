<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id',
        'inventory_transaction_id',
        'inventory_transaction_item_id',
        'type',
        'quantity',
        'stock_before',
        'stock_after',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'stock_before' => 'integer',
            'stock_after' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(InventoryTransaction::class, 'inventory_transaction_id');
    }

    public function transactionItem(): BelongsTo
    {
        return $this->belongsTo(InventoryTransactionItem::class, 'inventory_transaction_item_id');
    }
}
