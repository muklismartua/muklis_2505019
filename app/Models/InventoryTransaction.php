<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryTransaction extends Model
{
    public const TYPE_SALE = 'penjualan';

    public const TYPE_PURCHASE = 'pembelian';

    protected $fillable = [
        'transaction_number',
        'type',
        'transaction_date',
        'total',
        'notes',
        'user_id',
        'supplier_id',
        'customer_id',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'total' => 'integer',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryTransactionItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
