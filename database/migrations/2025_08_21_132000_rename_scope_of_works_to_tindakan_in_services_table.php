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
        Schema::table('services', function (Blueprint $table) {
            $table->jsonb('tindakan')->nullable()->after('description');
            $table->dropColumn('scope_of_works');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->jsonb('scope_of_works')->nullable()->after('description');
            $table->dropColumn('tindakan');
        });
    }
};
