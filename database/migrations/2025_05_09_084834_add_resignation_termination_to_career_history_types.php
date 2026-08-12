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
        DB::statement("ALTER TABLE career_histories DROP CONSTRAINT IF EXISTS career_histories_type_check");
        DB::statement("ALTER TABLE career_histories ALTER COLUMN type TYPE VARCHAR(255)");
        DB::statement("ALTER TABLE career_histories ADD CONSTRAINT career_histories_type_check CHECK (type IN ('promotion', 'mutation', 'demotion', 'transfer', 'resignation', 'termination'))");
        DB::statement("ALTER TABLE career_histories ALTER COLUMN type SET DEFAULT 'promotion'");

        DB::statement("ALTER TABLE employees DROP CONSTRAINT IF EXISTS employees_status_check");
        DB::statement("ALTER TABLE employees ALTER COLUMN status TYPE VARCHAR(255)");
        DB::statement("ALTER TABLE employees ADD CONSTRAINT employees_status_check CHECK (status IN ('active', 'inactive', 'on_leave', 'terminated'))");
        DB::statement("ALTER TABLE employees ALTER COLUMN status SET DEFAULT 'active'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
