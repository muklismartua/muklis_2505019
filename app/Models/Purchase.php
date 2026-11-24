<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Purchase extends Model
{
    public const STATUS_COMPLETED = 'selesai';

    public const STATUS_CANCELLED = 'batal';

    protected $fillable = [
        'nomor',
        'supplier_id',
        'user_id',
        'tanggal',
        'subtotal',
        'diskon',
        'total',
        'status_pembayaran',
        'jatuh_tempo',
        'total_dibayar',
        'sisa',
        'metode_pembayaran',
        'status',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'subtotal' => 'integer',
            'diskon' => 'integer',
            'total' => 'integer',
            'total_dibayar' => 'integer',
            'sisa' => 'integer',
            'jatuh_tempo' => 'date',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function hutangSupplier(): HasOne
    {
        return $this->hasOne(HutangSupplier::class);
    }
}
