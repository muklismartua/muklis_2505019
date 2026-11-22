<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class StockAdjustment extends Model
{
    protected $fillable = [
        'product_id',
        'qty_sistem',
        'qty_fisik',
        'selisih',
        'alasan',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'qty_sistem' => 'integer',
            'qty_fisik' => 'integer',
            'selisih' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stockMovement(): MorphOne
    {
        return $this->morphOne(StockMovement::class, 'referensi');
    }
}
