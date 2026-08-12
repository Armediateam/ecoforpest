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
        Schema::table('leader_reports', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['rejected_by']);
            
            $table->foreign('approved_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
                
            $table->foreign('rejected_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leader_reports', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['rejected_by']);
            
            $table->foreign('approved_by')
                ->references('id')
                ->on('employees')
                ->nullOnDelete();
                
            $table->foreign('rejected_by')
                ->references('id')
                ->on('employees')
                ->nullOnDelete();
        });
    }
};