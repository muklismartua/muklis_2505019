<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->enum('status_pembayaran', ['lunas', 'belum_lunas'])->default('lunas');
            $table->date('jatuh_tempo')->nullable();
            $table->unsignedBigInteger('total_dibayar')->default(0);
            $table->unsignedBigInteger('sisa')->default(0);
            $table->index(['status_pembayaran', 'jatuh_tempo']);
        });

        Schema::create('piutangs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('total');
            $table->unsignedBigInteger('terbayar')->default(0);
            $table->unsignedBigInteger('sisa');
            $table->date('jatuh_tempo')->nullable();
            $table->enum('status', ['belum_lunas', 'lunas', 'batal'])->default('belum_lunas');
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['jatuh_tempo', 'status']);
        });

        Schema::create('piutang_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('piutang_id')->constrained('piutangs')->cascadeOnDelete();
            $table->string('nomor')->unique();
            $table->date('tanggal');
            $table->unsignedBigInteger('jumlah');
            $table->enum('metode', ['tunai', 'transfer']);
            $table->text('catatan')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['piutang_id', 'tanggal']);
        });

        foreach (DB::table('sales')->orderBy('id')->get(['id', 'total', 'bayar', 'metode_pembayaran', 'status']) as $sale) {
            $paid = min((int) $sale->bayar, (int) $sale->total);
            $remaining = $sale->status === 'batal'
                ? 0
                : max((int) $sale->total - $paid, 0);

            DB::table('sales')
                ->where('id', $sale->id)
                ->update([
                    'status_pembayaran' => $remaining > 0 ? 'belum_lunas' : 'lunas',
                    'total_dibayar' => $paid,
                    'sisa' => $remaining,
                ]);
        }

        foreach (DB::table('receivables')->orderBy('id')->get() as $receivable) {
            $status = $receivable->status === 'batal'
                ? 'batal'
                : ((int) $receivable->sisa === 0 ? 'lunas' : 'belum_lunas');

            DB::table('piutangs')->insert([
                'sale_id' => $receivable->sale_id,
                'customer_id' => $receivable->customer_id,
                'total' => $receivable->total,
                'terbayar' => $receivable->terbayar,
                'sisa' => $receivable->sisa,
                'jatuh_tempo' => null,
                'status' => $status,
                'created_at' => $receivable->created_at,
                'updated_at' => $receivable->updated_at,
            ]);

            DB::table('sales')
                ->where('id', $receivable->sale_id)
                ->update([
                    'status_pembayaran' => $status === 'belum_lunas' ? 'belum_lunas' : 'lunas',
                    'total_dibayar' => $receivable->terbayar,
                    'sisa' => $status === 'batal' ? 0 : $receivable->sisa,
                ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('piutang_payments');
        Schema::dropIfExists('piutangs');

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['status_pembayaran', 'jatuh_tempo']);
            $table->dropColumn(['status_pembayaran', 'jatuh_tempo', 'total_dibayar', 'sisa']);
        });
    }
};
