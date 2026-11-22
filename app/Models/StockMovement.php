<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id',
        'tipe',
        'qty',
        'stok_sebelum',
        'stok_sesudah',
        'referensi_type',
        'referensi_id',
        'keterangan',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'stok_sebelum' => 'integer',
            'stok_sesudah' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function referensi(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
