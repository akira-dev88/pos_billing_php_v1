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
        Schema::create('customer_ledgers', function (Blueprint $table) {
            $table->id();

            $table->uuid('tenant_uuid');
            $table->uuid('customer_uuid');

            $table->string('type'); // sale, payment
            $table->decimal('amount', 10, 2);

            $table->string('reference_uuid')->nullable(); // sale_uuid or payment ref
            $table->string('note')->nullable();

            $table->timestamps();

            $table->index(['tenant_uuid', 'customer_uuid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_ledgers');
    }
};
