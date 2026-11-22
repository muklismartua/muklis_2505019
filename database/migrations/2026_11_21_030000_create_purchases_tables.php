<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('nomor')->unique();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('tanggal');
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('diskon')->default(0);
            $table->unsignedBigInteger('total');
            $table->string('metode_pembayaran', 30);
            $table->enum('status', ['selesai', 'batal'])->default('selesai');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['tanggal', 'status']);
            $table->index(['supplier_id', 'tanggal']);
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('qty');
            $table->unsignedBigInteger('harga_beli');
            $table->unsignedBigInteger('subtotal');
            $table->unique(['purchase_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
    }
};
