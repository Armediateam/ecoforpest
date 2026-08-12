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
        Schema::table('proposals', function (Blueprint $table) {
            $table->date('contract_start_date')->nullable()->change();
            $table->date('contract_end_date')->nullable()->change();
            $table->integer('warranty_term')->nullable()->change();
        });


        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn('warranty_type');
        });

        Schema::table('proposals', function (Blueprint $table) {
            $table->enum('warranty_type', ['day', 'month', 'year'])->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            //
        });
    }
};
