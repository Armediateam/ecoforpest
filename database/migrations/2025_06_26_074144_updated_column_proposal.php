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
        Schema::table('proposal_orders', function (Blueprint $table) {
            $table->float('subtotal')->nullable()->change();
            $table->float('discount_fixed')->nullable()->change();
            $table->float('discount_percent')->nullable()->change();
            $table->float('adjustment')->nullable()->change();
            $table->float('total')->nullable()->change();
            $table->text('target_detail')->nullable()->change();
            $table->text('client_note')->nullable()->change();
            $table->text('terms_condition')->nullable()->change();
        });

        Schema::table('proposal_items', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
            $table->text('description')->nullable()->change();
            $table->integer('qty')->nullable()->change();
            $table->string('qty_as')->nullable()->change();
            $table->string('unit')->nullable()->change();
            $table->float('rate')->nullable()->change();
            $table->float('amount')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
