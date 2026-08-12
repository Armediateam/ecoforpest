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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('address');
            $table->jsonb('position')->nullable();
            $table->string('email')->nullable();
            $table->foreignId('customer_group_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->foreignId('country_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('zip_code')->nullable();
            $table->string('default_language')->default('Indonesia');
            $table->string('website')->nullable();
            $table->string('company');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
