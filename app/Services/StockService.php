<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class StockService
{
    public function __construct(private readonly ActivityLogService $activityLogService) {}

    public function tambah(
        Product $product,
        int $qty,
        ?Model $referensi = null,
        ?string $keterangan = null,
        ?User $user = null,
    ): StockMovement {
        $this->validatePositiveQuantity($qty);

        return DB::transaction(function () use ($product, $qty, $referensi, $keterangan, $user): StockMovement {
            $lockedProduct = Product::query()->withTrashed()->lockForUpdate()->findOrFail($product->id);
            $stockBefore = $lockedProduct->stok;
            $lockedProduct->stok = $stockBefore + $qty;
            $lockedProduct->save();

            return $this->recordMovement(
                $lockedProduct,
                'masuk',
                $qty,
                $stockBefore,
                $lockedProduct->stok,
                $referensi,
                $keterangan,
                $user,
            );
        });
    }

    public function kurangi(
        Product $product,
        int $qty,
        ?Model $referensi = null,
        ?string $keterangan = null,
        ?User $user = null,
    ): StockMovement {
        $this->validatePositiveQuantity($qty);

        return DB::transaction(function () use ($product, $qty, $referensi, $keterangan, $user): StockMovement {
            $lockedProduct = Product::query()->withTrashed()->lockForUpdate()->findOrFail($product->id);
            $stockBefore = $lockedProduct->stok;

            if ($stockBefore < $qty) {
                throw ValidationException::withMessages([
                    'items' => "Stok {$lockedProduct->nama} tidak mencukupi. Stok tersedia: {$stockBefore}.",
                ]);
            }

            $lockedProduct->stok = $stockBefore - $qty;
            $lockedProduct->save();

            return $this->recordMovement(
                $lockedProduct,
                'keluar',
                $qty,
                $stockBefore,
                $lockedProduct->stok,
                $referensi,
                $keterangan,
                $user,
            );
        });
    }

    public function sesuaikan(
        Product $product,
        int $qtyFisik,
        string $alasan,
        ?User $user = null,
    ): StockMovement {
        if ($qtyFisik < 0) {
            throw ValidationException::withMessages([
                'qty_fisik' => 'Jumlah fisik tidak boleh kurang dari nol.',
            ]);
        }

        $reason = trim($alasan);
        if ($reason === '') {
            throw ValidationException::withMessages([
                'alasan' => 'Alasan penyesuaian wajib diisi.',
            ]);
        }

        return DB::transaction(function () use ($product, $qtyFisik, $reason, $user): StockMovement {
            $lockedProduct = Product::query()->withTrashed()->lockForUpdate()->findOrFail($product->id);
            $stockBefore = $lockedProduct->stok;
            $difference = $qtyFisik - $stockBefore;

            if ($difference === 0) {
                throw ValidationException::withMessages([
                    'qty_fisik' => 'Stok fisik sama dengan stok sistem; tidak ada penyesuaian untuk dicatat.',
                ]);
            }

            $actorId = $user?->id ?? Auth::id();
            $adjustment = StockAdjustment::query()->create([
                'product_id' => $lockedProduct->id,
                'qty_sistem' => $stockBefore,
                'qty_fisik' => $qtyFisik,
                'selisih' => $difference,
                'alasan' => $reason,
                'user_id' => $actorId,
            ]);

            $lockedProduct->stok = $qtyFisik;
            $lockedProduct->save();

            $movement = $this->recordMovement(
                $lockedProduct,
                'penyesuaian',
                abs($difference),
                $stockBefore,
                $qtyFisik,
                $adjustment,
                $reason,
                $user,
            );

            $this->activityLogService->catat(
                'stok.disesuaikan',
                "Stok {$lockedProduct->nama} disesuaikan dari {$stockBefore} menjadi {$qtyFisik}. Alasan: {$reason}",
                $adjustment,
                $user,
            );

            return $movement;
        });
    }

    private function validatePositiveQuantity(int $qty): void
    {
        if ($qty <= 0) {
            throw ValidationException::withMessages([
                'qty' => 'Jumlah pergerakan stok harus lebih besar dari nol.',
            ]);
        }
    }

    private function recordMovement(
        Product $product,
        string $type,
        int $qty,
        int $stockBefore,
        int $stockAfter,
        ?Model $referensi,
        ?string $keterangan,
        ?User $user,
    ): StockMovement {
        if (! in_array($type, ['masuk', 'keluar', 'penyesuaian'], true)) {
            throw new InvalidArgumentException('Jenis pergerakan stok tidak valid.');
        }

        return StockMovement::query()->create([
            'product_id' => $product->id,
            'tipe' => $type,
            'qty' => $qty,
            'stok_sebelum' => $stockBefore,
            'stok_sesudah' => $stockAfter,
            'referensi_type' => $referensi?->getMorphClass(),
            'referensi_id' => $referensi?->getKey(),
            'keterangan' => $keterangan,
            'user_id' => $user?->id ?? Auth::id(),
        ]);
    }
}
