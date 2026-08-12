<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('payment_url')->nullable();
            $table->string('payment_id')->nullable();
            $table->string('payment_status')->nullable();
            $table->timestamp('paid_at')->nullable();
        });
    }

    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['payment_url', 'payment_id', 'payment_status', 'paid_at']);
        });
    }
};
