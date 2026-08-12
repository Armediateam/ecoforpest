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
        // 1. Add default_shift_id to departments table
        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('default_shift_id')->nullable()->constrained('shifts')->onDelete('set null');
        });

        // 2. Add default_shift_id to positions table  
        Schema::table('positions', function (Blueprint $table) {
            $table->foreignId('default_shift_id')->nullable()->constrained('shifts')->onDelete('set null');
        });

        // 3. Add shift_id to employees table (for overrides)
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->onDelete('set null');
        });

        // 4. Drop the employee_shifts pivot table
        Schema::dropIfExists('employee_shifts');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Recreate employee_shifts table
        Schema::create('employee_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->timestamps();
        });

        // 2. Remove shift_id from employees
        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shift_id');
        });

        // 3. Remove default_shift_id from positions
        Schema::table('positions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_shift_id');
        });

        // 4. Remove default_shift_id from departments
        Schema::table('departments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_shift_id');
        });
    }
};
