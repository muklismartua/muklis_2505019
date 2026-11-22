<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();
            $table->timestamps();
        });

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();
            $table->string('singkatan', 20);
            $table->timestamps();
        });

        foreach (['suppliers', 'customers'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->string('nama');
                $table->string('telepon', 50)->nullable();
                $table->string('email')->nullable();
                $table->text('alamat')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('name', 'nama');
            $table->renameColumn('selling_price', 'harga_jual');
            $table->renameColumn('purchase_price', 'harga_beli');
            $table->renameColumn('stock', 'stok');
            $table->renameColumn('is_active', 'aktif');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedInteger('stok_minimum')->default(0);
            $table->string('foto')->nullable();
            $table->softDeletes();
        });

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_id');
            $table->dropConstrainedForeignId('customer_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->dropConstrainedForeignId('unit_id');
            $table->dropColumn(['stok_minimum', 'foto', 'deleted_at']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('nama', 'name');
            $table->renameColumn('harga_jual', 'selling_price');
            $table->renameColumn('harga_beli', 'purchase_price');
            $table->renameColumn('stok', 'stock');
            $table->renameColumn('aktif', 'is_active');
        });

        Schema::dropIfExists('customers');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('units');
        Schema::dropIfExists('categories');
    }
};
