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
        Schema::table('invoice_service_taxes', function (Blueprint $table) {
            // Drop the incorrect foreign key
            $table->dropForeign('invoice_service_taxes_invoice_service_id_foreign');
            
            // Add the correct foreign key
            $table->foreign('invoice_service_id')
                ->references('id')
                ->on('invoice_services')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_service_taxes', function (Blueprint $table) {
            $table->dropForeign(['invoice_service_id']);
            
            // Revert to the incorrect foreign key (technically referencing invoice_items as per original migration)
            $table->foreign('invoice_service_id')
                ->references('id')
                ->on('invoice_items')
                ->cascadeOnDelete();
        });
    }
};
