<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {

            $table->uuid('product_uuid')->primary();

            $table->uuid('tenant_uuid');

            $table->string('name');
            $table->string('barcode')->nullable();
            $table->string('sku')->nullable();

            $table->decimal('price', 10, 2);
            $table->decimal('gst_percent', 5, 2)->default(0);

            $table->integer('stock')->default(0);

            $table->timestamps();

            // 🔥 IMPORTANT for performance
            $table->index(['tenant_uuid']);
            $table->index(['barcode']);
            $table->index(['sku']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
