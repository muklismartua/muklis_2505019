<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'sku',
        'name',
        'selling_price',
        'purchase_price',
        'stock',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'selling_price' => 'integer',
            'purchase_price' => 'integer',
            'stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function transactionItems(): HasMany
    {
        return $this->hasMany(InventoryTransactionItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
