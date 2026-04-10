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
        Schema::create('purchases', function (Blueprint $table) {

            $table->uuid('purchase_uuid')->primary();
            $table->uuid('tenant_uuid');

            $table->decimal('total', 10, 2)->default(0);

            $table->string('supplier_name')->nullable();

            $table->timestamps();

            $table->index(['tenant_uuid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
