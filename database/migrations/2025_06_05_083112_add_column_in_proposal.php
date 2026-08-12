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
        Schema::table('proposals', function (Blueprint $table) {
            $table->date('approved_at')->nullable();
            $table->date('declined_at')->nullable();
            $table->foreignId('approver')->nullable()->constrained('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proposal', function (Blueprint $table) {
            $table->dropColumn([
                'approved_at',
                'declined_at',
                'approver'
            ]);
        });
    }
};
