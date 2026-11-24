<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receivable extends Model
{
    public const STATUS_UNPAID = 'belum_lunas';

    public const STATUS_PARTIAL = 'sebagian';

    public const STATUS_PAID = 'lunas';

    public const STATUS_CANCELLED = 'batal';

    protected $fillable = [
        'sale_id',
        'customer_id',
        'total',
        'terbayar',
        'sisa',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'integer',
            'terbayar' => 'integer',
            'sisa' => 'integer',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
