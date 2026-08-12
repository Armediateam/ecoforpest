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
        Schema::create('employee_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->geometry('position', subtype: 'POINT', srid: 4326)->nullable();
            $table->jsonb('info')->nullable();
            $table->timestamps();

            $table->index(['latitude', 'longitude']);
            $table->spatialIndex(['position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_locations', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
        });
        Schema::dropIfExists('employee_locations');
    }
};
