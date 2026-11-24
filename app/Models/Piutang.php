<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Piutang extends Model
{
    public const STATUS_UNPAID = 'belum_lunas';

    public const STATUS_PAID = 'lunas';

    public const STATUS_CANCELLED = 'batal';

    protected $fillable = [
        'sale_id',
        'customer_id',
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

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PiutangPayment::class);
    }
}
