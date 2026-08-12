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
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('cascade');
            $table->enum('status', ['draft', 'send', 'open', 'revised', 'declined', 'accepted']);
            $table->foreignId('assigned_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->enum('related', ['lead', 'customer']);
            $table->foreignId('lead_id')->nullable()->constrained('leads')->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('cascade');
            $table->string('subject');
            $table->string('to');
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->foreignId('country_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('zip_code')->nullable();
            $table->string('email');
            $table->string('phone');
            $table->foreignId('template_id')->nullable()->constrained('proposal_templates')->cascadeOnDelete();
            $table->foreignId('payment_term')->nullable()->constrained('contract_types')->cascadeOnDelete();
            $table->date('date');
            $table->date('open_till');
            $table->enum('discount_type', ['after_tax', 'before_tax']);
            $table->date('contract_start_date');
            $table->date('contract_end_date');
            $table->integer('warranty_term');
            $table->enum('warranty_type', ['day', 'month', 'year']);
            $table->boolean('allow_comments');
            $table->text('email_text')->nullable();
            $table->boolean('is_proposal_order');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
