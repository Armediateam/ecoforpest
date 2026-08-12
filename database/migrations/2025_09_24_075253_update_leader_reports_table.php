<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('leader_reports', function (Blueprint $table) {
            $table->integer('jumlah_customer')->nullable()->change();
            $table->integer('kunjungan_tepat_waktu')->nullable()->change();
            $table->boolean('ada_keterlambatan')->nullable()->change();
            $table->integer('penilaian_harian_skor')->nullable()->change();
            $table->boolean('peralatan_lengkap')->nullable()->change();
            $table->boolean('apd_lengkap')->nullable()->change();


            $table->decimal('kehadiran_tepat_waktu')->default(0); // Skor maks 10
            $table->decimal('tidak_terlambat')->default(0);       // Skor maks 5
            $table->decimal('izin_dengan_bukti')->default(0);     // Skor maks 5

            $table->decimal('jumlah_lokasi')->default(0);
            $table->decimal('kecepatan_treatment')->default(0);
            $table->decimal('update_laporan')->default(0);

            $table->decimal('penggunaan_apd')->default(0);
            $table->decimal('foto_dokumentasi')->default(0);
            $table->decimal('rating_kepuasan')->default(0);
            
            $table->decimal('laporan_sesuai_sop')->default(0);
            $table->decimal('penggunaan_aplikasi')->default(0);

            $table->decimal('tidak_kehilangan_alat')->default(0);
            $table->decimal('laporan_bahan_kimia')->default(0);

            $table->decimal('total_score')->default(0);

            $table->text('komentar_penilai')->nullable();
            $table->json('rekomendasi_sanksi')->nullable();
            $table->text('catatan_sanksi')->nullable();
        });
    }

    public function down()
    {
        Schema::table('leader_reports', function (Blueprint $table) {
            $table->dropColumn([
                'kehadiran_tepat_waktu',
                'tidak_terlambat',
                'izin_dengan_bukti',
                'jumlah_lokasi',
                'kecepatan_treatment',
                'update_laporan',
                'penggunaan_apd',
                'foto_dokumentasi',
                'rating_kepuasan',
                'laporan_sesuai_sop',
                'penggunaan_aplikasi',
                'tidak_kehilangan_alat',
                'laporan_bahan_kimia',
                'total_score',
                'komentar_penilai',
                'rekomendasi_sanksi',
                'catatan_sanksi',
            ]);

            $table->integer('jumlah_customer')->nullable(false)->change();
            $table->integer('kunjungan_tepat_waktu')->nullable(false)->change();
            $table->boolean('ada_keterlambatan')->nullable(false)->change();
            $table->integer('penilaian_harian_skor')->nullable(false)->change();
            $table->boolean('peralatan_lengkap')->nullable(false)->change();
            $table->boolean('apd_lengkap')->nullable(false)->change();
        });
    }
};