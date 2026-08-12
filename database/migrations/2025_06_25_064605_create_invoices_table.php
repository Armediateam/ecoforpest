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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('status');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('cascade');
            $table->text('billing_address')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_state')->nullable();
            $table->string('billing_zip_code')->nullable();
            $table->string('billing_country')->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_state')->nullable();
            $table->string('shipping_zip_code')->nullable();
            $table->string('shipping_country')->nullable();
            $table->string('invoice_number');
            $table->foreignId('payment_term')->nullable()->constrained('contract_types')->cascadeOnDelete();
            $table->date('invoice_date');
            $table->date('invoice_due_date');
            $table->string('allowed_payment_method');
            $table->foreignId('sale_agent')->nullable()->constrained('users')->onDelete('cascade');
            $table->enum('discount_type', ['after_tax', 'before_tax']);
            $table->string('recuring_invoices');
            $table->foreignId('work_order_id')->nullable()->constrained('work_orders')->onDelete('cascade');
            $table->text('admin_note')->nullable();
            $table->float('subtotal')->nullable();
            $table->float('discount_fixed')->nullable();
            $table->float('discount_percent')->nullable();
            $table->float('adjustment')->nullable();
            $table->float('total')->nullable();
            $table->text('target_detail')->nullable();
            $table->text('client_note')->nullable();
            $table->text('terms_condition')->nullable();
            $table->text('email_text')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
