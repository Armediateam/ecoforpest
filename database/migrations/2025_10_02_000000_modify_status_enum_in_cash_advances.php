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
        // Drop existing check constraint first
        DB::statement('ALTER TABLE cash_advances DROP CONSTRAINT IF EXISTS cash_advances_status_check');
        
        // Add new check constraint with updated enum values
        DB::statement("ALTER TABLE cash_advances ADD CONSTRAINT cash_advances_status_check CHECK (status::text = ANY (ARRAY['pending'::text, 'approved'::text, 'rejected'::text, 'paid'::text]))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the new constraint
        DB::statement('ALTER TABLE cash_advances DROP CONSTRAINT IF EXISTS cash_advances_status_check');
        
        // Restore original constraint
        DB::statement("ALTER TABLE cash_advances ADD CONSTRAINT cash_advances_status_check CHECK (status::text = ANY (ARRAY['pending'::text, 'paid'::text]))");
    }
};