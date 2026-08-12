<?php

namespace Database\Seeders;

use App\Models\SurveyForm;
use Illuminate\Database\Seeder;

class SurveyFormSeeder extends Seeder
{
    public function run(): void
    {
        // Identification Form
        SurveyForm::create([
            'name' => 'Identifikasi Awal',
            'type' => SurveyForm::TYPE_IDENTIFICATION,
            'is_active' => true,
            'fields' => [
                [
                    'id' => 'customer_confirmation',
                    'type' => 'radio',
                    'label' => 'Konfirmasi Kehadiran Customer',
                    'required' => true,
                    'options' => ['Hadir', 'Tidak Hadir']
                ],
                [
                    'id' => 'location_type',
                    'type' => 'select',
                    'label' => 'Jenis Lokasi',
                    'required' => true,
                    'options' => ['Rumah', 'Kantor', 'Gudang', 'Pabrik', 'Lainnya']
                ],
                [
                    'id' => 'building_size',
                    'type' => 'number',
                    'label' => 'Ukuran Bangunan (m²)',
                    'required' => true,
                ],
                [
                    'id' => 'notes',
                    'type' => 'textarea',
                    'label' => 'Catatan Tambahan',
                    'required' => false,
                ]
            ]
        ]);

        // Initial Check Form
        SurveyForm::create([
            'name' => 'Pemeriksaan Awal',
            'type' => SurveyForm::TYPE_INITIAL_CHECK,
            'is_active' => true,
            'fields' => [
                [
                    'id' => 'pest_types',
                    'type' => 'checkbox',
                    'label' => 'Jenis Hama yang Ditemukan',
                    'required' => true,
                    'options' => ['Tikus', 'Kecoa', 'Semut', 'Rayap', 'Lalat', 'Nyamuk', 'Lainnya']
                ],
                [
                    'id' => 'infestation_level',
                    'type' => 'radio',
                    'label' => 'Tingkat Infestasi',
                    'required' => true,
                    'options' => ['Ringan', 'Sedang', 'Berat']
                ],
                [
                    'id' => 'affected_areas',
                    'type' => 'checkbox',
                    'label' => 'Area yang Terdampak',
                    'required' => true,
                    'options' => ['Dapur', 'Kamar Mandi', 'Gudang', 'Ruang Tamu', 'Kamar Tidur', 'Halaman', 'Lainnya']
                ],
                [
                    'id' => 'photos',
                    'type' => 'file',
                    'label' => 'Foto Kondisi Awal',
                    'required' => true,
                    'multiple' => true,
                    'accept' => 'image/*'
                ],
                [
                    'id' => 'initial_notes',
                    'type' => 'textarea',
                    'label' => 'Catatan Pemeriksaan',
                    'required' => false,
                ]
            ]
        ]);

        // Final Check Form
        SurveyForm::create([
            'name' => 'Pemeriksaan Akhir',
            'type' => SurveyForm::TYPE_FINAL_CHECK,
            'is_active' => true,
            'fields' => [
                [
                    'id' => 'treatment_completed',
                    'type' => 'checkbox',
                    'label' => 'Tindakan yang Telah Dilakukan',
                    'required' => true,
                    'options' => ['Penyemprotan', 'Pemasangan Perangkap', 'Fogging', 'Pemberian Umpan', 'Sanitasi']
                ],
                [
                    'id' => 'effectiveness',
                    'type' => 'radio',
                    'label' => 'Efektivitas Penanganan',
                    'required' => true,
                    'options' => ['Sangat Efektif', 'Efektif', 'Cukup Efektif', 'Kurang Efektif']
                ],
                [
                    'id' => 'customer_satisfaction',
                    'type' => 'radio',
                    'label' => 'Kepuasan Pelanggan',
                    'required' => true,
                    'options' => ['Sangat Puas', 'Puas', 'Cukup Puas', 'Kurang Puas']
                ],
                [
                    'id' => 'photos_after',
                    'type' => 'file',
                    'label' => 'Foto Kondisi Akhir',
                    'required' => true,
                    'multiple' => true,
                    'accept' => 'image/*'
                ],
                [
                    'id' => 'recommendations',
                    'type' => 'textarea',
                    'label' => 'Rekomendasi',
                    'required' => true,
                ],
                [
                    'id' => 'customer_signature',
                    'type' => 'signature',
                    'label' => 'Tanda Tangan Customer',
                    'required' => true,
                ]
            ]
        ]);
    }
}
