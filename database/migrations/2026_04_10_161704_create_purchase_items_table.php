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
        Schema::create('purchase_items', function (Blueprint $table) {

            $table->id();

            $table->uuid('purchase_uuid');
            $table->uuid('product_uuid');

            $table->integer('quantity');
            $table->decimal('cost_price', 10, 2);

            $table->timestamps();

            $table->index(['purchase_uuid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
