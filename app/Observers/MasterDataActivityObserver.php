<?php

namespace App\Observers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Model;

class MasterDataActivityObserver
{
    public function created(Model $model): void
    {
        $this->record('master_data.dibuat', $model);
    }

    public function updated(Model $model): void
    {
        if ($model instanceof Product) {
            $changedFields = array_diff(array_keys($model->getChanges()), ['stok', 'updated_at']);
            if ($changedFields === []) {
                return;
            }
        }

        $this->record('master_data.diperbarui', $model);
    }

    public function deleted(Model $model): void
    {
        $this->record('master_data.dihapus', $model);
    }

    private function record(string $action, Model $model): void
    {
        $label = match (true) {
            $model instanceof Category => 'Kategori',
            $model instanceof Unit => 'Satuan',
            $model instanceof Supplier => 'Supplier',
            $model instanceof Customer => 'Pelanggan',
            $model instanceof Product => 'Produk',
            default => class_basename($model),
        };
        $name = $model->getAttribute('nama') ?? $model->getAttribute('sku') ?? $model->getKey();
        $verb = match ($action) {
            'master_data.dibuat' => 'ditambahkan',
            'master_data.diperbarui' => 'diperbarui',
            default => 'dihapus',
        };

        app(ActivityLogService::class)->catat(
            $action,
            "{$label} {$name} berhasil {$verb}.",
            $model,
        );
    }
}
