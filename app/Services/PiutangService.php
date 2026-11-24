<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Piutang;
use App\Models\PiutangPayment;
use App\Models\Receivable;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PiutangService
{
    public function __construct(
        private readonly NomorTransaksiService $nomorTransaksiService,
        private readonly ActivityLogService $activityLogService,
    ) {}

    public function buatDariPenjualan(Sale $sale, int $dpDibayar, ?string $jatuhTempo): ?Piutang
    {
        return DB::transaction(function () use ($sale, $dpDibayar, $jatuhTempo): ?Piutang {
            $sale = Sale::query()->lockForUpdate()->findOrFail($sale->id);

            if ($sale->customer_id === null) {
                throw ValidationException::withMessages([
                    'customer_id' => 'Pelanggan wajib dipilih untuk membuat piutang.',
                ]);
            }

            if ($dpDibayar < 0 || $dpDibayar > $sale->total) {
                throw ValidationException::withMessages([
                    'bayar' => 'Pembayaran awal tidak valid untuk pembuatan piutang.',
                ]);
            }

            if (Piutang::query()->where('sale_id', $sale->id)->exists()) {
                throw ValidationException::withMessages([
                    'sale' => 'Piutang untuk penjualan ini sudah dibuat.',
                ]);
            }

            $remaining = $sale->total - $dpDibayar;
            if ($remaining === 0) {
                $sale->update([
                    'status_pembayaran' => 'lunas',
                    'jatuh_tempo' => null,
                    'total_dibayar' => $dpDibayar,
                    'sisa' => 0,
                ]);

                return null;
            }

            $status = Piutang::STATUS_UNPAID;
            $piutang = Piutang::query()->create([
                'sale_id' => $sale->id,
                'customer_id' => $sale->customer_id,
                'total' => $sale->total,
                'terbayar' => $dpDibayar,
                'sisa' => $remaining,
                'jatuh_tempo' => $jatuhTempo,
                'status' => $status,
            ]);

            $sale->update([
                'status_pembayaran' => $status === Piutang::STATUS_PAID ? 'lunas' : 'belum_lunas',
                'jatuh_tempo' => $jatuhTempo,
                'total_dibayar' => $dpDibayar,
                'sisa' => $remaining,
            ]);

            $this->syncLegacyReceivable($piutang);

            return $piutang;
        });
    }

    public function catatPembayaran(
        Piutang $piutang,
        int $jumlah,
        string $metode,
        ?string $catatan = null,
    ): PiutangPayment {
        if ($jumlah <= 0) {
            throw ValidationException::withMessages([
                'jumlah' => 'Jumlah pembayaran harus lebih besar dari nol.',
            ]);
        }

        if (! in_array($metode, ['tunai', 'transfer'], true)) {
            throw ValidationException::withMessages([
                'metode' => 'Metode pembayaran piutang tidak valid.',
            ]);
        }

        return DB::transaction(function () use ($piutang, $jumlah, $metode, $catatan): PiutangPayment {
            $sale = Sale::query()->lockForUpdate()->findOrFail($piutang->sale_id);
            $piutang = Piutang::query()->lockForUpdate()->findOrFail($piutang->id);

            if ($piutang->status !== Piutang::STATUS_UNPAID || $jumlah > $piutang->sisa) {
                throw ValidationException::withMessages([
                    'jumlah' => $piutang->status === Piutang::STATUS_CANCELLED
                        ? 'Piutang yang dibatalkan tidak dapat menerima pembayaran.'
                        : 'Jumlah pembayaran tidak boleh melebihi sisa piutang.',
                ]);
            }

            $remaining = $piutang->sisa - $jumlah;
            $paid = $piutang->terbayar + $jumlah;
            $status = $remaining === 0 ? Piutang::STATUS_PAID : Piutang::STATUS_UNPAID;

            $payment = PiutangPayment::query()->create([
                'piutang_id' => $piutang->id,
                'nomor' => $this->nomorTransaksiService->nomorPembayaranPiutang(),
                'tanggal' => now()->toDateString(),
                'jumlah' => $jumlah,
                'metode' => $metode,
                'catatan' => $catatan,
                'user_id' => auth()->id(),
            ]);

            $piutang->update([
                'terbayar' => $paid,
                'sisa' => $remaining,
                'status' => $status,
            ]);
            $sale->update([
                'status_pembayaran' => $status === Piutang::STATUS_PAID ? 'lunas' : 'belum_lunas',
                'total_dibayar' => $paid,
                'sisa' => $remaining,
            ]);

            $piutang->refresh();
            $this->syncLegacyReceivable($piutang);
            $this->activityLogService->catat(
                'piutang.pembayaran_dicatat',
                "Pembayaran {$payment->nomor} sebesar Rp ".number_format($jumlah, 0, ',', '.')." dicatat untuk piutang penjualan {$sale->nomor}.",
                $payment,
            );

            return $payment;
        });
    }

    public function batalkanDariPenjualan(Sale $sale): ?Piutang
    {
        return DB::transaction(function () use ($sale): ?Piutang {
            $sale = Sale::query()->lockForUpdate()->findOrFail($sale->id);
            $piutang = Piutang::query()
                ->where('sale_id', $sale->id)
                ->lockForUpdate()
                ->first();

            if ($piutang !== null) {
                if ($piutang->payments()->exists()) {
                    throw ValidationException::withMessages([
                        'sale' => 'Penjualan tidak dapat dibatalkan karena sudah ada pembayaran piutang. Hubungi admin untuk membatalkan pembayaran terlebih dahulu.',
                    ]);
                }

                $piutang->update([
                    'status' => Piutang::STATUS_CANCELLED,
                    'sisa' => 0,
                ]);
                $piutang->refresh();
                $this->syncLegacyReceivable($piutang, cancelled: true);
            } else {
                Receivable::query()->where('sale_id', $sale->id)->update([
                    'sisa' => 0,
                    'status' => Receivable::STATUS_CANCELLED,
                ]);
            }

            $sale->update([
                'status_pembayaran' => 'lunas',
                'sisa' => 0,
            ]);

            return $piutang;
        });
    }

    public function totalHutangPelanggan(Customer $customer): int
    {
        return (int) Piutang::query()
            ->where('customer_id', $customer->id)
            ->where('status', Piutang::STATUS_UNPAID)
            ->sum('sisa');
    }

    public function cekBatasHutang(Customer $customer, int $tambahan): void
    {
        if ($tambahan < 0) {
            throw ValidationException::withMessages([
                'customer_id' => 'Tambahan hutang tidak boleh bernilai negatif.',
            ]);
        }

        $limit = $customer->batas_hutang;
        if ($limit === 0) {
            return;
        }

        $currentDebt = $this->totalHutangPelanggan($customer);
        if ($tambahan > PHP_INT_MAX - $currentDebt || $currentDebt + $tambahan > $limit) {
            throw ValidationException::withMessages([
                'customer_id' => 'Batas hutang pelanggan terlampaui. Hutang saat ini Rp '
                    .number_format($currentDebt, 0, ',', '.').', tambahan Rp '
                    .number_format($tambahan, 0, ',', '.').', batas Rp '
                    .number_format($limit, 0, ',', '.').'.',
            ]);
        }
    }

    private function syncLegacyReceivable(Piutang $piutang, bool $cancelled = false): void
    {
        $remaining = $cancelled ? 0 : $piutang->sisa;
        $status = $cancelled || $piutang->status === Piutang::STATUS_CANCELLED
            ? Receivable::STATUS_CANCELLED
            : ($remaining === 0
                ? Receivable::STATUS_PAID
                : ($piutang->terbayar > 0 ? Receivable::STATUS_PARTIAL : Receivable::STATUS_UNPAID));

        Receivable::query()->updateOrCreate(
            ['sale_id' => $piutang->sale_id],
            [
                'customer_id' => $piutang->customer_id,
                'total' => $piutang->total,
                'terbayar' => $piutang->terbayar,
                'sisa' => $remaining,
                'status' => $status,
            ],
        );
    }
}
