<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class MasterDataService
{
    public function deleteLookup(Model $record): void
    {
        $inUse = match (true) {
            $record instanceof Category, $record instanceof Unit => $record->products()->withTrashed()->exists(),
            $record instanceof Supplier => $record->transactions()->exists() || $record->purchases()->exists(),
            $record instanceof Customer => $record->transactions()->exists() || $record->sales()->exists(),
            default => throw new \InvalidArgumentException('Jenis data master tidak didukung.'),
        };

        if ($inUse) {
            throw ValidationException::withMessages([
                'record' => "Data {$record->nama} tidak dapat dihapus karena masih digunakan.",
            ]);
        }

        $record->delete();
    }
}
