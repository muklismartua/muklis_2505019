<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HutangSupplier extends Model
{
    public const STATUS_UNPAID = 'belum_lunas';

    public const STATUS_PAID = 'lunas';

    public const STATUS_CANCELLED = 'batal';

    protected $fillable = [
        'purchase_id',
        'supplier_id',
        'total',
        'terbayar',
        'sisa',
        'jatuh_tempo',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'integer',
            'terbayar' => 'integer',
            'sisa' => 'integer',
            'jatuh_tempo' => 'date',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(HutangSupplierPayment::class);
    }
}
