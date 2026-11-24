<?php

namespace App\Services;

use App\Models\HutangSupplier;
use App\Models\HutangSupplierPayment;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HutangSupplierService
{
    public function __construct(
        private readonly NomorTransaksiService $nomorTransaksiService,
        private readonly ActivityLogService $activityLogService,
    ) {}

    public function buatDariPembelian(
        Purchase $purchase,
        int $dpDibayar,
        ?string $jatuhTempo,
        ?User $user = null,
        string $metodeDp = 'tunai',
    ): ?HutangSupplier {
        return DB::transaction(function () use ($purchase, $dpDibayar, $jatuhTempo, $user, $metodeDp): ?HutangSupplier {
            $purchase = Purchase::query()->lockForUpdate()->findOrFail($purchase->id);

            if ($dpDibayar < 0 || $dpDibayar > $purchase->total) {
                throw ValidationException::withMessages([
                    'bayar' => 'Uang muka tidak boleh melebihi total pembelian.',
                ]);
            }

            $sisa = $purchase->total - $dpDibayar;
            if ($sisa > 0 && ($jatuhTempo === null || $jatuhTempo <= now()->toDateString())) {
                throw ValidationException::withMessages([
                    'jatuh_tempo' => 'Tanggal jatuh tempo hutang harus setelah hari ini.',
                ]);
            }
            $purchase->update([
                'status_pembayaran' => $sisa > 0 ? HutangSupplier::STATUS_UNPAID : HutangSupplier::STATUS_PAID,
                'jatuh_tempo' => $sisa > 0 ? $jatuhTempo : null,
                'total_dibayar' => $dpDibayar,
                'sisa' => $sisa,
            ]);

            if ($sisa === 0) {
                return null;
            }

            if ($purchase->hutangSupplier()->exists()) {
                throw ValidationException::withMessages([
                    'purchase' => 'Hutang untuk pembelian ini sudah tercatat.',
                ]);
            }

            $hutang = HutangSupplier::query()->create([
                'purchase_id' => $purchase->id,
                'supplier_id' => $purchase->supplier_id,
                'total' => $purchase->total,
                'terbayar' => $dpDibayar,
                'sisa' => $sisa,
                'jatuh_tempo' => $jatuhTempo,
                'status' => HutangSupplier::STATUS_UNPAID,
            ]);

            $this->activityLogService->catat(
                'hutang_supplier.dibuat',
                "Hutang untuk pembelian {$purchase->nomor} berhasil dibuat.",
                $hutang,
                $user,
            );

            if ($dpDibayar > 0) {
                $payment = $hutang->payments()->create([
                    'nomor' => $this->nomorTransaksiService->nomorPembayaranHutangSupplier(),
                    'tanggal' => now()->toDateString(),
                    'jumlah' => $dpDibayar,
                    'metode' => $metodeDp,
                    'catatan' => "Uang muka pembelian {$purchase->nomor}",
                    'user_id' => $user?->id,
                ]);
                $this->activityLogService->catat(
                    'hutang_supplier.pembayaran_dicatat',
                    'Uang muka sebesar Rp '.number_format($dpDibayar, 0, ',', '.')." dicatat untuk pembelian {$purchase->nomor}.",
                    $payment,
                    $user,
                );
            }

            return $hutang;
        });
    }

    public function catatPembayaran(
        HutangSupplier $hutang,
        int $jumlah,
        string $metode,
        ?string $catatan,
        User $user,
    ): HutangSupplierPayment {
        return DB::transaction(function () use ($hutang, $jumlah, $metode, $catatan, $user): HutangSupplierPayment {
            $purchaseId = HutangSupplier::query()->whereKey($hutang->id)->value('purchase_id');
            $purchase = Purchase::query()->lockForUpdate()->findOrFail($purchaseId);
            $hutang = HutangSupplier::query()->lockForUpdate()->findOrFail($hutang->id);

            if ($hutang->status !== HutangSupplier::STATUS_UNPAID || $hutang->sisa <= 0) {
                throw ValidationException::withMessages([
                    'jumlah' => 'Hutang ini sudah lunas atau dibatalkan.',
                ]);
            }

            if ($jumlah <= 0 || $jumlah > $hutang->sisa) {
                throw ValidationException::withMessages([
                    'jumlah' => 'Jumlah pembayaran harus lebih dari Rp 0 dan tidak boleh melebihi sisa hutang.',
                ]);
            }

            $payment = $hutang->payments()->create([
                'nomor' => $this->nomorTransaksiService->nomorPembayaranHutangSupplier(),
                'tanggal' => now()->toDateString(),
                'jumlah' => $jumlah,
                'metode' => $metode,
                'catatan' => $catatan,
                'user_id' => $user->id,
            ]);
            $terbayar = $hutang->terbayar + $jumlah;
            $sisa = $hutang->sisa - $jumlah;
            $status = $sisa === 0 ? HutangSupplier::STATUS_PAID : HutangSupplier::STATUS_UNPAID;

            $hutang->update([
                'terbayar' => $terbayar,
                'sisa' => $sisa,
                'status' => $status,
            ]);
            $purchase->update([
                'status_pembayaran' => $status,
                'total_dibayar' => $terbayar,
                'sisa' => $sisa,
            ]);

            $this->activityLogService->catat(
                'hutang_supplier.pembayaran_dicatat',
                "Pembayaran {$payment->nomor} sebesar Rp ".number_format($jumlah, 0, ',', '.')." dicatat untuk pembelian {$purchase->nomor}.",
                $payment,
                $user,
            );

            return $payment;
        });
    }

    public function batalkan(Purchase $purchase, ?User $user = null): ?HutangSupplier
    {
        return DB::transaction(function () use ($purchase, $user): ?HutangSupplier {
            $purchase = Purchase::query()->lockForUpdate()->findOrFail($purchase->id);
            $hutang = HutangSupplier::query()
                ->where('purchase_id', $purchase->id)
                ->lockForUpdate()
                ->first();

            if ($hutang === null) {
                return null;
            }

            if ($hutang->terbayar > 0 || $hutang->payments()->exists()) {
                throw ValidationException::withMessages([
                    'purchase' => 'Pembelian tidak dapat dibatalkan karena uang muka atau pembayaran hutang supplier sudah tercatat.',
                ]);
            }

            if ($hutang->status !== HutangSupplier::STATUS_CANCELLED) {
                $hutang->update([
                    'status' => HutangSupplier::STATUS_CANCELLED,
                    'sisa' => 0,
                ]);
                $purchase->update([
                    'status_pembayaran' => HutangSupplier::STATUS_PAID,
                    'jatuh_tempo' => null,
                    'sisa' => 0,
                ]);
                $this->activityLogService->catat(
                    'hutang_supplier.dibatalkan',
                    "Hutang untuk pembelian {$purchase->nomor} dibatalkan.",
                    $hutang,
                    $user,
                );
            }

            return $hutang;
        });
    }
}
