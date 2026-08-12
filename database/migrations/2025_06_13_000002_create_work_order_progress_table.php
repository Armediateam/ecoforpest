<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_order_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->enum('progress_status', [
                'Take Order', 
                'Ketemu Client',
                'Survey',
                'Mulai Kerja',
                'Tindakan',
                'Selesai Kerja',
                'Collect Money'
            ]);
            $table->text('notes')->nullable();
            $table->json('photos')->nullable();
            $table->json('location')->nullable();
            $table->timestamp('completed_at');
            $table->foreignId('completed_by')->constrained('employees');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_progress');
    }
};
