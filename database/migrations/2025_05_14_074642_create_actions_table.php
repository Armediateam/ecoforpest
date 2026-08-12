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
        Schema::create('actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('order_of_action');
            $table->boolean('isEnable')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actions');
    }
};
