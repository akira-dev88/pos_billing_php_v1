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

        Schema::table('purchases', function (Blueprint $table) {
            // Add new column
            $table->uuid('supplier_uuid')->nullable()->after('total');

            // Drop old column
            $table->dropColumn('supplier_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            // Recreate old column
            $table->string('supplier_name')->nullable();

            // Drop new column
            $table->dropColumn('supplier_uuid');
        });
    }
};
