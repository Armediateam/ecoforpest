<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddServiceIdToSurveyFormsTable extends Migration
{
    public function up(): void
    {
        Schema::table('survey_forms', function (Blueprint $table) {
            $table->foreignId('service_id')->nullable()->after('fields')->constrained('services')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('survey_forms', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->dropColumn('service_id');
        });
    }
};
