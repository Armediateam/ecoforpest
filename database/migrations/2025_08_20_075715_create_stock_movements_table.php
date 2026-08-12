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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->nullable()->constrained('items')->onDelete('cascade');
            $table->float('quantity')->default(0);
            $table->enum('transaction_type', ['STOCK_IN', 'STOCK_OUT', 'ADJUSTMENT'])->default('STOCK_IN');
            $table->string('reference')->nullable();
            $table->foreignId('work_order_id')->nullable()->constrained('work_orders')->onDelete('cascade');
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->onDelete('cascade');
            $table->foreignId('proposal_id')->nullable()->constrained('proposals')->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
