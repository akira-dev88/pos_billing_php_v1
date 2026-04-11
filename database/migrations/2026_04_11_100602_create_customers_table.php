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
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('customer_uuid')->primary();
            $table->uuid('tenant_uuid');

            $table->string('name');
            $table->string('mobile')->nullable();
            $table->string('address')->nullable();
            $table->string('gstin')->nullable();

            $table->decimal('credit_balance', 10, 2)->default(0);

            $table->timestamps();

            $table->index(['tenant_uuid', 'mobile']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
