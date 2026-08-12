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
        Schema::create('schedule_plannings', function (Blueprint $table) {
            $table->id();
            $table->string('client_name');
            $table->text('address');
            $table->string('location_maps_url');
            $table->date('treatment_start_date');
            $table->json('schedule_days'); // ['monday', 'wednesday', etc]
            $table->string('visit_hours');
            $table->text('night_treatment')->nullable();
            $table->json('target_pests');
            $table->string('visit_frequency');
            
            // Treatment methods for each week
            $table->json('week_one_treatments');
            $table->json('week_two_treatments');
            $table->json('week_three_treatments');
            $table->json('week_four_treatments');
            
            $table->text('leader_notes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_plannings');
    }
};
