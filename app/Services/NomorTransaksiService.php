<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\PiutangPayment;
use App\Models\Sale;
use App\Models\StockManual;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NomorTransaksiService
{
    public function __construct(private readonly StoreSettingsService $storeSettingsService) {}

    public function nomorPembelian(): string
    {
        return $this->nomorHarian(
            Purchase::class,
            $this->storeSettingsService->get('prefix_pembelian'),
        );
    }

    public function nomorPenjualan(): string
    {
        return $this->nomorHarian(
            Sale::class,
            $this->storeSettingsService->get('prefix_penjualan'),
        );
    }

    public function nomorStokManual(): string
    {
        return $this->nomorHarian(StockManual::class, 'SM');
    }

    public function nomorPembayaranPiutang(): string
    {
        return $this->nomorHarian(PiutangPayment::class, 'BYR');
    }

    /**
     * @param  class-string<Model>  $modelClass
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
