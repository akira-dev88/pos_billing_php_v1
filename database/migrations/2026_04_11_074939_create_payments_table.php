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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_uuid');
            $table->uuid('sale_uuid');

            $table->string('method'); // cash, upi, card, credit
            $table->decimal('amount', 10, 2);

            $table->string('reference')->nullable(); // txn id, upi ref, etc
            $table->timestamps();

            $table->index(['tenant_uuid', 'sale_uuid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
