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
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->string('clock_in_status')->nullable()->after('clock_in');
            $table->string('clock_out_status')->nullable()->after('clock_out');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->enum('status', ['present', 'late', 'absent', 'on_leave'])->after('clock_out');
            $table->dropColumn('clock_in_status');
            $table->dropColumn('clock_out_status');
        });
    }
};
