<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->boolean('is_leader')->default(false)->after('name');
        });
    }

    public function down()
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->dropColumn('is_leader');
        });
    }
};
