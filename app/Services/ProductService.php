<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ProductService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?UploadedFile $photo): Product
    {
        $path = $this->storePhoto($photo);

        try {
            return DB::transaction(function () use ($data, $path): Product {
                $data['foto'] = $path;
                $data['stok'] = 0;

                return Product::query()->create($data);
            });
        } catch (Throwable $exception) {
            $this->deletePhoto($path);
            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data, ?UploadedFile $photo): Product
    {
        $newPath = $this->storePhoto($photo);
        $oldPath = $product->foto;

        try {
            DB::transaction(function () use ($product, $data, $newPath): void {
                unset($data['stok'], $data['foto']);

                if ($newPath !== null) {
                    $data['foto'] = $newPath;
                }

                $product->update($data);
            });
        } catch (Throwable $exception) {
            $this->deletePhoto($newPath);
            throw $exception;
        }

        if ($newPath !== null && $oldPath !== null) {
            $this->deletePhoto($oldPath);
        }

        return $product->refresh();
    }

    public function delete(Product $product): void
    {
        $photo = $product->foto;
        $product->delete();

        if ($photo !== null) {
            $this->deletePhoto($photo);
        }
    }

    private function storePhoto(?UploadedFile $photo): ?string
    {
        if ($photo === null) {
            return null;
        }

        $path = $photo->store('products', 'public');

        if ($path === false) {
            throw new RuntimeException('Foto produk gagal disimpan.');
        }

        return $path;
    }

    private function deletePhoto(?string $path): void
    {
        if ($path !== null && ! Storage::disk('public')->delete($path)) {
            throw new RuntimeException('Foto produk gagal dihapus dari penyimpanan.');
        }
    }
}
