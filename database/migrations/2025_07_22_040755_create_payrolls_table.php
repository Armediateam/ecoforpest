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
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('period_start_date');
            $table->date('period_end_date');
            $table->integer('work_days')->default(0);
            $table->integer('leave_days')->default(0);
            $table->integer('permission_days')->default(0);
            $table->integer('absent_days')->default(0);
            $table->integer('overtime_hours')->default(0);
            $table->jsonb('employee_income')->nullable();
            $table->jsonb('employee_expense')->nullable();
            $table->integer('final_salary');
            $table->foreignId('generated_by')->constrained('users')->cascadeOnDelete();
            $table->dateTime('generated_at');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
