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
        Schema::create('stock_ledgers', function (Blueprint $table) {

            $table->id();

            $table->uuid('tenant_uuid');
            $table->uuid('product_uuid');

            $table->integer('quantity'); // +ve or -ve

            $table->string('type');
            // sale | purchase | adjustment | return

            $table->uuid('reference_uuid')->nullable();
            // sale_uuid or purchase_uuid

            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['tenant_uuid']);
            $table->index(['product_uuid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_ledgers');
    }
};
