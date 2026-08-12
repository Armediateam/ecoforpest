<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_forms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // identification, initial_check, final_check
            $table->boolean('is_active')->default(true);
            $table->json('fields'); // Store form fields configuration
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('work_order_surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('survey_form_id')->constrained('survey_forms')->cascadeOnDelete();
            $table->json('answers'); // Store survey answers
            $table->foreignId('filled_by')->constrained('employees');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_surveys');
        Schema::dropIfExists('survey_forms');
    }
};
