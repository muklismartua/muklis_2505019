<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PiutangPayment extends Model
{
    protected $fillable = [
        'piutang_id',
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

    public function piutang(): BelongsTo
    {
        return $this->belongsTo(Piutang::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
