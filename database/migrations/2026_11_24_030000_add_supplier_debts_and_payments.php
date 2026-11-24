<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->enum('status_pembayaran', ['lunas', 'belum_lunas'])->default('lunas');
            $table->date('jatuh_tempo')->nullable();
            $table->unsignedBigInteger('total_dibayar')->default(0);
            $table->unsignedBigInteger('sisa')->default(0);
            $table->index(['status_pembayaran', 'jatuh_tempo']);
        });

        DB::table('purchases')->update([
            'status_pembayaran' => 'lunas',
            'total_dibayar' => DB::raw('total'),
            'sisa' => 0,
        ]);

        Schema::create('hutang_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('total');
            $table->unsignedBigInteger('terbayar')->default(0);
            $table->unsignedBigInteger('sisa');
            $table->date('jatuh_tempo')->nullable();
            $table->enum('status', ['belum_lunas', 'lunas', 'batal'])->default('belum_lunas');
            $table->timestamps();

            $table->index(['supplier_id', 'status']);
            $table->index(['jatuh_tempo', 'status']);
        });

        Schema::create('hutang_supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hutang_supplier_id')->constrained('hutang_suppliers')->cascadeOnDelete();
            $table->string('nomor')->unique();
            $table->date('tanggal');
            $table->unsignedBigInteger('jumlah');
            $table->enum('metode', ['tunai', 'transfer']);
            $table->text('catatan')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['hutang_supplier_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hutang_supplier_payments');
        Schema::dropIfExists('hutang_suppliers');

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex(['status_pembayaran', 'jatuh_tempo']);
            $table->dropColumn(['status_pembayaran', 'jatuh_tempo', 'total_dibayar', 'sisa']);
        });
    }
};
