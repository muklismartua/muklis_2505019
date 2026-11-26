<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class StockManual extends Model
{
    public const TYPE_ADD = 'tambah';

    public const TYPE_SUBTRACT = 'kurang';

    public const REASONS = [
        'barang_rusak' => 'Barang rusak',
        'barang_hilang' => 'Barang hilang',
        'kadaluarsa' => 'Kadaluarsa',
        'sampel' => 'Sampel',
        'hadiah' => 'Hadiah',
        'stok_awal' => 'Stok awal',
        'koreksi' => 'Koreksi',
        'lainnya' => 'Lainnya',
    ];

    protected $fillable = [
        'nomor',
        'product_id',
        'tipe',
        'qty',
        'alasan',
        'keterangan',
        'user_id',
        'tanggal',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'tanggal' => 'date',
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

    public function getAlasanLabelAttribute(): string
    {
        return self::REASONS[$this->alasan] ?? $this->alasan;
    }
}
