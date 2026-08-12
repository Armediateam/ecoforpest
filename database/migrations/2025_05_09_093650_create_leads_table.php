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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('status_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('source_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('asassigned_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('tag_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('position');
            $table->string('email');
            $table->string('city');
            $table->string('state');
            $table->foreignId('country_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('zip_code');
            $table->string('default_language')->nullable();
            $table->double('lead_value');
            $table->string('website');
            $table->string('company');
            $table->text('description')->nullable();
            $table->dateTime('date_contacted');
            $table->boolean('is_public')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
