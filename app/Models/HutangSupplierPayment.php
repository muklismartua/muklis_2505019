<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HutangSupplierPayment extends Model
{
    protected $fillable = [
        'hutang_supplier_id',
        'nomor',
        'tanggal',
        'jumlah',
        'metode',
        'catatan',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jumlah' => 'integer',
        ];
    }

    public function hutangSupplier(): BelongsTo
    {
        return $this->belongsTo(HutangSupplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
