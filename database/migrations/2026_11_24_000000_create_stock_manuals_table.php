<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_manuals', function (Blueprint $table) {
            $table->id();
            $table->string('nomor')->unique();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->enum('tipe', ['tambah', 'kurang']);
            $table->unsignedInteger('qty');
            $table->enum('alasan', [
                'barang_rusak',
                'barang_hilang',
                'kadaluarsa',
                'sampel',
                'hadiah',
                'stok_awal',
                'koreksi',
                'lainnya',
            ]);
            $table->text('keterangan')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('tanggal');
            $table->timestamps();

            $table->index(['tanggal', 'tipe']);
            $table->index(['tanggal', 'alasan']);
            $table->index(['product_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_manuals');
    }
};
