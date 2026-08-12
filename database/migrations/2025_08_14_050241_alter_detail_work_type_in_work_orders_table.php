<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Mengubah tipe kolom detail_work menjadi TEXT (PostgreSQL)
        DB::statement('ALTER TABLE work_orders ALTER COLUMN detail_work TYPE TEXT');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Mengembalikan tipe kolom ke VARCHAR(255)
        DB::statement("ALTER TABLE work_orders ALTER COLUMN detail_work TYPE VARCHAR(255)");
    }
};
