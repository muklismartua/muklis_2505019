<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'purchase_id',
        'product_id',
        'qty',
        'harga_beli',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'harga_beli' => 'integer',
            'subtotal' => 'integer',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
