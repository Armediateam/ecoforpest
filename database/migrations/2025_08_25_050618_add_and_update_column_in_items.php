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
        Schema::table('items', function (Blueprint $table) {
            $table->string('ingredients')->nullable();
            $table->string('produced_by')->nullable();
            $table->string('utilty')->nullable();
            $table->string('application')->nullable();
            $table->integer('sell_price')->nullable();
            $table->integer('warehouse_price')->nullable();
            $table->integer('expenses')->nullable();
            $table->integer('good_condition')->nullable();
            $table->integer('bad_condition')->nullable();
            $table->enum('type', ['potion', 'tools'])->nullable();
            $table->foreignId('tool_category_id')->nullable()->constrained('tools_categories')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropForeign(['tool_category_id']);
            $table->dropColumn([
                'ingredients',
                'produced_by',
                'utilty',
                'application',
                'sell_price',
                'warehouse_price',
                'expenses',
                'good_condition',
                'bad_condition',
                'type',
                'tool_category_id'
            ]);
        });
    }
};
