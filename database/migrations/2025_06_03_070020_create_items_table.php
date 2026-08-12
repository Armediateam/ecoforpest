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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->text('barcode');
            $table->string('sku_code')->nullable();
            $table->string('sku_name')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('item_group_id')->constrained('item_groups')->cascadeOnDelete();
            $table->float('rate');
            $table->float('purchase_price');
            $table->integer('min_inventory_qty');
            $table->integer('max_inventory_qty');
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('tax_id')->constrained('taxes')->cascadeOnDelete();
            $table->text('attachment')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
