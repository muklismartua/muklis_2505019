<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Support\Str;

class NomorTransaksiService
{
    public function nomorPembelian(): string
    {
        return $this->nomorHarian(Purchase::class, 'PB');
    }

    public function nomorPenjualan(): string
    {
        return $this->nomorHarian(Sale::class, 'PJ');
    }

    /**
     * @param  class-string<Purchase|Sale>  $modelClass
     */
    private function nomorHarian(string $modelClass, string $kode): string
    {
        $tanggal = now()->format('Ymd');
        $prefix = "{$kode}-{$tanggal}-";
        $nomorTerakhir = $modelClass::query()
            ->where('nomor', 'like', $prefix.'%')
            ->orderByDesc('nomor')
            ->lockForUpdate()
            ->value('nomor');
        $urutan = $nomorTerakhir === null ? 1 : ((int) Str::afterLast($nomorTerakhir, '-')) + 1;

        return $prefix.str_pad((string) $urutan, 4, '0', STR_PAD_LEFT);
    }
}
