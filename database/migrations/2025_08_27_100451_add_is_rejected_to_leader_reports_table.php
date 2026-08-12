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
            $table->boolean('is_rejected')->default(false);
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->foreign('rejected_by')->references('id')->on('employees')->onDelete('set null');
            $table->text('rejection_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leader_reports', function (Blueprint $table) {
            $table->dropForeign(['rejected_by']);
            $table->dropColumn(['is_rejected', 'rejected_by', 'rejected_at']);
        });
    }
};
