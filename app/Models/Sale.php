<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Sale extends Model
{
    public const STATUS_COMPLETED = 'selesai';

    public const STATUS_CANCELLED = 'batal';

    public const PAYMENT_CASH = 'tunai';

    public const PAYMENT_TRANSFER = 'transfer';

    public const PAYMENT_TEMPO = 'tempo';

    protected $fillable = [
        'nomor',
        'customer_id',
        'user_id',
        'tanggal',
        'subtotal',
        'diskon',
        'pajak',
        'total',
        'bayar',
        'kembalian',
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
            'pajak' => 'integer',
            'total' => 'integer',
            'bayar' => 'integer',
            'kembalian' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function receivable(): HasOne
    {
        return $this->hasOne(Receivable::class);
    }
}
