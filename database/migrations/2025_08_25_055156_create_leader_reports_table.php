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
        Schema::create('leader_reports', function (Blueprint $table) {
            $table->id();
            $table->date('periode_laporan');
            $table->foreignId('leader_id')->constrained('employees');
            $table->foreignId('teknisi_id')->constrained('employees');
            $table->integer('jumlah_customer');
            $table->integer('kunjungan_tepat_waktu');
            $table->boolean('ada_keterlambatan');
            $table->text('keterlambatan_detail')->nullable();
            $table->integer('penilaian_harian_skor');
            $table->boolean('peralatan_lengkap');
            $table->text('peralatan_tidak_lengkap_detail')->nullable();
            $table->boolean('apd_lengkap');
            $table->text('catatan_tambahan')->nullable();
            $table->string('bukti_penilaian')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leader_reports');
    }
};
