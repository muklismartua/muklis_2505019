<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('kode')->nullable()->unique();
            $table->unsignedBigInteger('batas_hutang')->default(0);
            $table->boolean('aktif')->default(true);
        });

        foreach (DB::table('customers')->orderBy('id')->get(['id']) as $customer) {
            DB::table('customers')
                ->where('id', $customer->id)
                ->update(['kode' => 'PLG-'.str_pad((string) $customer->id, 4, '0', STR_PAD_LEFT)]);
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->string('kode')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['kode', 'batas_hutang', 'aktif']);
        });
    }
};
