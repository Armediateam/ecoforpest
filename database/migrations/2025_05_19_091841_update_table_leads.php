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
        Schema::table('leads', function (Blueprint $table) {
            $table->text('address')->nullable(false)->change();
            $table->dropColumn('position');
            $table->string('email')->nullable()->change();
            $table->string('city')->nullable()->change();
            $table->string('state')->nullable()->change();
            $table->string('zip_code')->nullable()->change();
            $table->string('default_language')->default('Indonesia')->change();
            $table->string('website')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn([
                'name',
                'address',
                'email',
                'city',
                'state',
                'zip_code',
                'default_language',
                'website',
            ]);
        });
    }
};
