<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = ['nama', 'telepon', 'email', 'alamat'];

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class)->where('type', InventoryTransaction::TYPE_SALE);
    }
}
