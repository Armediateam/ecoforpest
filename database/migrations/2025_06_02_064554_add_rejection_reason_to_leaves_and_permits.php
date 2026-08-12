<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('status');
        });

        Schema::table('permits', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('status');
        });

        Schema::table('overtimes', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
        });

        Schema::table('permits', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
        });

        Schema::table('overtimes', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
        });
    }
};
