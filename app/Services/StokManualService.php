<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockManual;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StokManualService
{
    public function __construct(
        private readonly NomorTransaksiService $nomorTransaksiService,
        private readonly StockService $stockService,
        private readonly ActivityLogService $activityLogService,
    ) {}

    /**
     * @param  array{
     *     tipe: string,
     *     alasan: string,
     *     keterangan?: ?string,
     *     tanggal: string,
     *     items: list<array{product_id: int|string, qty: int|string}>
     * }  $data
     * @return Collection<int, StockManual>
     */
    public function simpan(array $data, User $user): Collection
    {
        return DB::transaction(function () use ($data, $user): Collection {
            $type = $data['tipe'] ?? '';
            if (! in_array($type, [StockManual::TYPE_ADD, StockManual::TYPE_SUBTRACT], true)) {
                throw ValidationException::withMessages([
                    'tipe' => 'Tipe perubahan stok tidak valid.',
                ]);
            }

            $reason = $data['alasan'] ?? '';
            if (! array_key_exists($reason, StockManual::REASONS)) {
                throw ValidationException::withMessages([
                    'alasan' => 'Alasan perubahan stok tidak valid.',
                ]);
            }

            $items = collect($data['items'] ?? []);
            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Tambahkan minimal satu produk.',
                ]);
            }

            $productIds = $items->pluck('product_id')->map(fn ($id) => (int) $id);
            if ($productIds->duplicates()->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Produk yang sama tidak boleh ditambahkan lebih dari satu kali.',
                ]);
            }

            foreach ($items as $index => $item) {
                if ((int) ($item['qty'] ?? 0) <= 0) {
                    throw ValidationException::withMessages([
                        "items.{$index}.qty" => 'Jumlah perubahan stok harus lebih besar dari nol.',
                    ]);
                }
            }

            $products = Product::query()
                ->whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== $productIds->unique()->count()) {
                throw ValidationException::withMessages([
                    'items' => 'Salah satu produk tidak lagi tersedia.',
                ]);
            }

            $manualIds = [];
            foreach ($items as $item) {
                $product = $products->get((int) $item['product_id']);
                $qty = (int) $item['qty'];
                $manual = StockManual::query()->create([
                    'nomor' => $this->nomorTransaksiService->nomorStokManual(),
                    'product_id' => $product->id,
                    'tipe' => $type,
                    'qty' => $qty,
                    'alasan' => $reason,
                    'keterangan' => $data['keterangan'] ?? null,
                    'user_id' => $user->id,
                    'tanggal' => $data['tanggal'],
                ]);

                $description = $manual->alasan_label;
                if ($manual->keterangan !== null && trim($manual->keterangan) !== '') {
                    $description .= ': '.trim($manual->keterangan);
                } else {
                    $description .= " ({$manual->nomor})";
                }

                if ($type === StockManual::TYPE_ADD) {
                    $this->stockService->tambah($product, $qty, $manual, $description, $user);
                } else {
                    $this->stockService->kurangi($product, $qty, $manual, $description, $user);
                }

                $action = $type === StockManual::TYPE_ADD ? 'ditambah' : 'dikurangi';
                $this->activityLogService->catat(
                    'stok_manual.dibuat',
                    "Stok {$product->nama} {$action} {$qty}. Alasan: {$manual->alasan_label}. Referensi {$manual->nomor}.",
                    $manual,
                    $user,
                );

                $manualIds[] = $manual->id;
            }

            return StockManual::query()
                ->with(['product', 'user'])
                ->whereIn('id', $manualIds)
                ->orderBy('id')
                ->get();
        });
    }
}
