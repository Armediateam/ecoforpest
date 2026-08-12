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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('clock_in');
            $table->time('clock_out');
            $table->string('status')->default('present'); // 'present', 'late', 'absent', 'on_leave'
            $table->text('notes')->nullable();
            $table->boolean('is_leave')->default(false);
            $table->string('leave_type')->nullable();
            $table->text('leave_reason')->nullable();
            $table->text('image_clock_in')->nullable();
            $table->text('image_clock_out')->nullable();
            $table->jsonb('coordinate_clock_in');
            $table->jsonb('coordinate_clock_out');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
