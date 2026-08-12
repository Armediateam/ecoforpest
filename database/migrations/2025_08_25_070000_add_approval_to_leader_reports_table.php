<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('leader_reports', function (Blueprint $table) {
            $table->boolean('is_approved')->default(false);
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreign('approved_by')->references('id')->on('employees')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('leader_reports', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['is_approved', 'approved_by', 'approved_at']);
        });
    }
};
