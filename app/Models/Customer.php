<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = ['nama', 'telepon', 'email', 'alamat', 'batas_hutang', 'aktif'];

    protected function casts(): array
    {
        return [
            'batas_hutang' => 'integer',
            'aktif' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Customer $customer): void {
            if ($customer->kode === null) {
                $customer->kode = 'PLG-TMP-'.Str::uuid();
            }
        });

        static::created(function (Customer $customer): void {
            if (str_starts_with((string) $customer->kode, 'PLG-TMP-')) {
                $customer->forceFill([
                    'kode' => 'PLG-'.str_pad((string) $customer->id, 4, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class)->where('type', InventoryTransaction::TYPE_SALE);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function receivables(): HasMany
    {
        return $this->hasMany(Receivable::class);
    }

    public function piutangs(): HasMany
    {
        return $this->hasMany(Piutang::class);
    }
}
