<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('service_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('work_order_id');
            $table->string('customer_name');
            $table->string('work_order_number');
            $table->dateTime('close_order')->nullable();
            $table->string('technician_name');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->boolean('email_sent')->default(false);
            $table->boolean('client_approve')->default(false);
            $table->boolean('technician_approve')->default(false);
            $table->string('signature_token')->unique();
            $table->string('signature_url')->nullable();
            $table->string('client_signature')->nullable();
            $table->string('client_signature_name')->nullable();
            $table->string('technician_signature')->nullable();
            $table->string('technician_signature_name')->nullable();
            $table->timestamps();

            $table->foreign('work_order_id')->references('id')->on('work_orders')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_reports');
    }
};
