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
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('position_id')->nullable()->constrained('positions')->onDelete('cascade');
        });

        Schema::table('positions', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->constrained()->onDelete('cascade');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('manager_id')->nullable()->constrained('employees');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('position_id');
        });

        Schema::table('positions', function (Blueprint $table) {
            $table->dropColumn('department_id');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('manager_id');
        });
    }
};
