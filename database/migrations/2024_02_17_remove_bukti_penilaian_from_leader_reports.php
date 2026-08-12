<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('leader_reports', function (Blueprint $table) {
            $table->dropColumn('bukti_penilaian');
        });
    }

    public function down()
    {
        Schema::table('leader_reports', function (Blueprint $table) {
            $table->string('bukti_penilaian')->nullable();
        });
    }
};
