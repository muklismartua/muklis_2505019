<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private StockService $stockService;

    private Product $product;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stockService = app(StockService::class);
        $this->product = Product::factory()->create(['stok' => 10]);
        $this->user = User::factory()->create();
    }

    public function test_tambah_increases_stock_and_records_a_positive_movement(): void
    {
        $movement = $this->stockService->tambah($this->product, 5, null, 'Stok awal', $this->user);

        $this->assertSame(15, $this->product->fresh()->stok);
        $this->assertSame('masuk', $movement->tipe);
        $this->assertSame(5, $movement->qty);
        $this->assertSame(10, $movement->stok_sebelum);
        $this->assertSame(15, $movement->stok_sesudah);
        $this->assertSame($this->user->id, $movement->user_id);
        $this->assertSame('Stok awal', $movement->keterangan);
    }

    public function test_kurangi_decreases_stock_and_records_a_movement(): void
    {
        $movement = $this->stockService->kurangi($this->product, 4, null, 'Pemakaian', $this->user);

        $this->assertSame(6, $this->product->fresh()->stok);
        $this->assertSame('keluar', $movement->tipe);
        $this->assertSame(4, $movement->qty);
        $this->assertSame(10, $movement->stok_sebelum);
        $this->assertSame(6, $movement->stok_sesudah);
    }

    public function test_kurangi_throws_and_leaves_stock_unchanged_when_stock_is_insufficient(): void
    {
        try {
            $this->stockService->kurangi($this->product, 11, null, null, $this->user);
            $this->fail('Expected an insufficient-stock validation exception.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('items', $exception->errors());
        }

        $this->assertSame(10, $this->product->fresh()->stok);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_sesuaikan_saves_the_physical_count_and_movement(): void
    {
        $movement = $this->stockService->sesuaikan($this->product, 7, 'Hasil penghitungan fisik', $this->user);
        $adjustment = StockAdjustment::query()->firstOrFail();

        $this->assertSame(7, $this->product->fresh()->stok);
        $this->assertSame(10, $adjustment->qty_sistem);
        $this->assertSame(7, $adjustment->qty_fisik);
        $this->assertSame(-3, $adjustment->selisih);
        $this->assertSame('penyesuaian', $movement->tipe);
        $this->assertSame(3, $movement->qty);
        $this->assertSame(StockAdjustment::class, $movement->referensi_type);
        $this->assertSame($adjustment->id, $movement->referensi_id);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->user->id,
            'aksi' => 'stok.disesuaikan',
            'subject_type' => StockAdjustment::class,
            'subject_id' => $adjustment->id,
        ]);
    }

    public function test_movement_quantity_must_be_positive(): void
    {
        $this->expectException(ValidationException::class);

        $this->stockService->tambah($this->product, 0);
    }
}
