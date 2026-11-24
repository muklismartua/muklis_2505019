<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'sku',
        'nama',
        'category_id',
        'unit_id',
        'harga_beli',
        'harga_jual',
        'stok',
        'stok_minimum',
        'foto',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'harga_jual' => 'integer',
            'harga_beli' => 'integer',
            'stok' => 'integer',
            'stok_minimum' => 'integer',
            'aktif' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function transactionItems(): HasMany
    {
        return $this->hasMany(InventoryTransactionItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stockManuals(): HasMany
    {
        return $this->hasMany(StockManual::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}
