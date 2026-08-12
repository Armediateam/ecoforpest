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
        Schema::create('task_recurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->nullable()->constrained('tasks')->onDelete('cascade');
            $table->enum('frequency', ['Daily', 'Weekly', 'Monthly', 'Yearly'])->default('Daily');
            $table->integer('interval');
            $table->string('days_of_week')->nullable();
            $table->integer('days_of_month')->nullable();
            $table->integer('total_cycle')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_recurrences');
    }
};
