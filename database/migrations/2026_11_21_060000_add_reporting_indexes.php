<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'tanggal'], 'sales_user_status_date_index');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'tanggal'], 'purchases_user_status_date_index');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index(['aktif', 'category_id'], 'products_active_category_index');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->index('product_id', 'sale_items_product_index');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->index('product_id', 'purchase_items_product_index');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index('created_at', 'stock_movements_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('stock_movements_created_at_index');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropIndex('purchase_items_product_index');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropIndex('sale_items_product_index');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_active_category_index');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex('purchases_user_status_date_index');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_user_status_date_index');
        });
    }
};
