<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmployeeLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employees = \App\Models\Employee::where('status', 'active')->take(10)->get();

        // Jakarta area coordinates for demo
        $jakartaLatRange = [-6.3, -6.1]; // South to North
        $jakartaLngRange = [106.7, 106.9]; // West to East

        foreach ($employees as $employee) {
            // Generate random coordinates within Jakarta area
            $latitude = $this->randomFloat($jakartaLatRange[0], $jakartaLatRange[1], 6);
            $longitude = $this->randomFloat($jakartaLngRange[0], $jakartaLngRange[1], 6);

            // Create or update location
            \App\Models\EmployeeLocations::renewLocation([
                'employee_id' => $employee->id,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'info' => [
                    'formatted_address' => $this->generateAddress(),
                    'accuracy' => rand(5, 50),
                    'source' => 'mobile_app',
                    'battery_level' => rand(20, 100),
                ]
            ]);
        }
    }

    private function randomFloat($min, $max, $decimals = 2)
    {
        $scale = pow(10, $decimals);
        return mt_rand($min * $scale, $max * $scale) / $scale;
    }

    private function generateAddress()
    {
        $streets = [
            'Jl. Sudirman',
            'Jl. Thamrin',
            'Jl. Gatot Subroto',
            'Jl. Rasuna Said',
            'Jl. Kuningan',
            'Jl. Casablanca',
            'Jl. Senopati',
            'Jl. Panglima Polim',
            'Jl. Kemang',
            'Jl. Radio Dalam',
            'Jl. Fatmawati',
            'Jl. Cilandak'
        ];

        $areas = [
            'Menteng',
            'Kebayoran Baru',
            'Senayan',
            'Kuningan',
            'Kemang',
            'Pondok Indah',
            'Cilandak',
            'Jakarta Selatan',
            'Jakarta Pusat'
        ];

        $street = $streets[array_rand($streets)];
        $area = $areas[array_rand($areas)];
        $number = rand(1, 999);

        return "{$street} No. {$number}, {$area}, Jakarta";
    }
}
