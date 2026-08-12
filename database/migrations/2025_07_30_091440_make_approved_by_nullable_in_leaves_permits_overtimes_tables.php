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
        // Make approved_by nullable in leaves table
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->foreignId('approved_by')->nullable()->change()->constrained('users')->cascadeOnDelete();
        });

        // Make approved_by nullable in permits table
        Schema::table('permits', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->foreignId('approved_by')->nullable()->change()->constrained('users')->cascadeOnDelete();
        });

        // Make approved_by nullable in overtimes table
        Schema::table('overtimes', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->foreignId('approved_by')->nullable()->change()->constrained('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert approved_by to non-nullable in leaves table
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->foreignId('approved_by')->change()->constrained('users')->cascadeOnDelete();
        });

        // Revert approved_by to non-nullable in permits table
        Schema::table('permits', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->foreignId('approved_by')->change()->constrained('users')->cascadeOnDelete();
        });

        // Revert approved_by to non-nullable in overtimes table
        Schema::table('overtimes', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->foreignId('approved_by')->change()->constrained('users')->cascadeOnDelete();
        });
    }
};
