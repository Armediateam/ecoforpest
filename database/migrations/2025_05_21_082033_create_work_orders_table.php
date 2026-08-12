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
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->text('alamat');
            $table->jsonb('position');
            $table->text('address_note');
            $table->float('total')->default(0);
            $table->string('detail_work');
            $table->enum('related', ['lead', 'customer']);
            $table->foreignId('lead_id')->nullable()->constrained('leads')->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('cascade');
            $table->date('work_date');
            $table->time('work_time');
            $table->boolean('is_recuring');
            $table->integer('repeat_every')->nullable();
            $table->enum('repeat_type', ['day', 'week', 'month', 'year'])->nullable();
            $table->integer('repeat_cycle')->nullable();
            $table->foreignId('assigned_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
