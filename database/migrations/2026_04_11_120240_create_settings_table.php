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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();

            $table->uuid('tenant_uuid')->unique();

            $table->string('shop_name');
            $table->string('mobile')->nullable();
            $table->string('address')->nullable();
            $table->string('gstin')->nullable();

            $table->string('invoice_prefix')->default('INV');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
