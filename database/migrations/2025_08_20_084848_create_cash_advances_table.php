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
        Schema::create('cash_advances', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('description');
            $table->decimal('amount', 16, 2);
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->string('reference')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('paid_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_advances');
    }
};
