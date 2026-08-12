<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('finance_transactions', function (Blueprint $table) {
            // First, convert existing decimal amounts to integer (multiply by 100)
            DB::statement('UPDATE finance_transactions SET amount = amount * 100');

            // Change column type to bigint
            $table->bigInteger('amount')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finance_transactions', function (Blueprint $table) {
            // Convert back to decimal and divide by 100
            DB::statement('UPDATE finance_transactions SET amount = amount / 100');

            // Change column type back to decimal
            $table->decimal('amount', 16, 2)->change();
        });
    }
};
