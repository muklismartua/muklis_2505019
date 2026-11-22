<?php

namespace App\Services;

use App\Models\Purchase;
use Illuminate\Support\Str;

class NomorTransaksiService
{
    public function nomorPembelian(): string
    {
        $tanggal = now()->format('Ymd');
        $prefix = "PB-{$tanggal}-";
        $nomorTerakhir = Purchase::query()
            ->where('nomor', 'like', $prefix.'%')
            ->orderByDesc('nomor')
            ->lockForUpdate()
            ->value('nomor');
        $urutan = $nomorTerakhir === null ? 1 : ((int) Str::afterLast($nomorTerakhir, '-')) + 1;

        return $prefix.str_pad((string) $urutan, 4, '0', STR_PAD_LEFT);
    }
}
