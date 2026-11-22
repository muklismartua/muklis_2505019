<?php

use App\Models\InventoryTransaction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->nullableMorphs('referensi');
            $table->text('keterangan')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
        });

        DB::table('stock_movements')
            ->whereNotNull('inventory_transaction_id')
            ->orderBy('id')
            ->chunkById(100, function ($movements): void {
                foreach ($movements as $movement) {
                    $userId = DB::table('inventory_transactions')
                        ->where('id', $movement->inventory_transaction_id)
                        ->value('user_id');

                    DB::table('stock_movements')
                        ->where('id', $movement->id)
                        ->update([
                            'referensi_type' => InventoryTransaction::class,
                            'referensi_id' => $movement->inventory_transaction_id,
                            'user_id' => $userId,
                        ]);
                }
            });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->renameColumn('type', 'tipe');
            $table->renameColumn('quantity', 'qty');
            $table->renameColumn('stock_before', 'stok_sebelum');
            $table->renameColumn('stock_after', 'stok_sesudah');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->enum('tipe', ['masuk', 'keluar', 'penyesuaian'])->change();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['inventory_transaction_id']);
            $table->dropForeign(['inventory_transaction_item_id']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropColumn(['inventory_transaction_id', 'inventory_transaction_item_id']);
        });

        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('qty_sistem');
            $table->unsignedInteger('qty_fisik');
            $table->integer('selisih');
            $table->text('alasan');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('inventory_transaction_id')
                ->nullable()
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('inventory_transaction_item_id')
                ->nullable()
                ->constrained('inventory_transaction_items')
                ->restrictOnDelete();
        });

        DB::table('stock_movements')
            ->where('referensi_type', InventoryTransaction::class)
            ->update(['inventory_transaction_id' => DB::raw('referensi_id')]);

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'keterangan']);
            $table->dropMorphs('referensi');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->string('tipe', 20)->change();
            $table->renameColumn('tipe', 'type');
            $table->renameColumn('qty', 'quantity');
            $table->renameColumn('stok_sebelum', 'stock_before');
            $table->renameColumn('stok_sesudah', 'stock_after');
        });
    }
};
