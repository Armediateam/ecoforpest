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
        Schema::table('departments', function (Blueprint $table) {
            // Drop the existing foreign key if it exists
            // The default constraint name Laravel would have generated: departments_manager_id_foreign
            $table->dropForeign(['manager_id']);
        });

        Schema::table('departments', function (Blueprint $table) {
            // Ensure the column is nullable (should already be, but enforce for safety)
            $table->foreignId('manager_id')->nullable()->change();
            // Recreate FK with nullOnDelete (SET NULL on delete employee)
            $table->foreign('manager_id')
                ->references('id')
                ->on('employees')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
        });

        Schema::table('departments', function (Blueprint $table) {
            // Recreate original constraint WITHOUT null on delete (restrict behavior)
            $table->foreign('manager_id')
                ->references('id')
                ->on('employees');
        });
    }
};
