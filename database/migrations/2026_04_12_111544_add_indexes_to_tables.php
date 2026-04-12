<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // PRODUCTS
        Schema::table('products', function (Blueprint $table) {
            if (!$this->indexExists('products', 'products_barcode_index')) {
                $table->index('barcode');
            }
            if (!$this->indexExists('products', 'products_sku_index')) {
                $table->index('sku');
            }
        });

        // SALES
        Schema::table('sales', function (Blueprint $table) {
            if (!$this->indexExists('sales', 'sales_invoice_number_index')) {
                $table->index('invoice_number');
            }
            if (!$this->indexExists('sales', 'sales_created_at_index')) {
                $table->index('created_at');
            }
        });

        // SALE ITEMS
        Schema::table('sale_items', function (Blueprint $table) {
            if (!$this->indexExists('sale_items', 'sale_items_sale_uuid_index')) {
                $table->index('sale_uuid');
            }
        });

        // CUSTOMERS
        Schema::table('customers', function (Blueprint $table) {
            if (!$this->indexExists('customers', 'customers_mobile_index')) {
                $table->index('mobile');
            }
        });
    }

    private function indexExists($table, $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table}");
        foreach ($indexes as $index) {
            if ($index->Key_name === $indexName) {
                return true;
            }
        }
        return false;
    }

    public function down(): void {}
};
