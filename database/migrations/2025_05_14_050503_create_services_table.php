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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('isScheduled')->default(false);
            $table->integer('scheduled');
            $table->time('jam_buka')->nullable();
            $table->time('jam_tutup')->nullable();
            $table->integer('quota_order_per_day');
            $table->boolean('isCameraEnabled')->default(false);
            $table->boolean('isMinimumOrder')->default(false);
            $table->integer('minimum_order');
            $table->double('price');
            $table->boolean('isEnablePriceBusy')->default(false);
            $table->time('busy_hour_start')->nullable();
            $table->time('busy_hour_end')->nullable();
            $table->double('price_busy_hour');
            $table->integer('estimate_time_order');
            $table->text('description');
            $table->text('image')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
