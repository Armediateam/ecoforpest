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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('package_type_id')->nullable()->constrained()->cascadeOnDelete();
            $table->integer('point');
            $table->string('estimation_time');
            $table->string('package_guarantee')->nullable();
            $table->double('min_price');
            $table->double('max_price');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->string('password')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
