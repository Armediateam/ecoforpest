<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Banks setting for payment methods
        Setting::updateOrCreate(
            ['key' => 'banks'],
            [
                'value' => [
                    'Tunai' => 'Jangan Berikan Tunai ke Teknisi'
                ],
                'form_type' => 'key_value',
                'description' => 'Bank accounts and payment methods available for invoices'
            ]
        );
    }
}
