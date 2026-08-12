<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Shift;

class ShiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shifts = [
            [
                'name' => 'Regular Shift',
                'workhour' => [
                    [
                        'day' => 'monday',
                        'start_time' => '08:00',
                        'end_time' => '17:00',
                        'break_start' => '12:00',
                        'break_end' => '13:00',
                        'working_hours' => 8
                    ],
                    [
                        'day' => 'tuesday',
                        'start_time' => '08:00',
                        'end_time' => '17:00',
                        'break_start' => '12:00',
                        'break_end' => '13:00',
                        'working_hours' => 8
                    ],
                    [
                        'day' => 'wednesday',
                        'start_time' => '08:00',
                        'end_time' => '17:00',
                        'break_start' => '12:00',
                        'break_end' => '13:00',
                        'working_hours' => 8
                    ],
                    [
                        'day' => 'thursday',
                        'start_time' => '08:00',
                        'end_time' => '17:00',
                        'break_start' => '12:00',
                        'break_end' => '13:00',
                        'working_hours' => 8
                    ],
                    [
                        'day' => 'friday',
                        'start_time' => '08:00',
                        'end_time' => '17:00',
                        'break_start' => '12:00',
                        'break_end' => '13:00',
                        'working_hours' => 8
                    ]
                ]
            ],
            [
                'name' => 'Morning Shift',
                'workhour' => [
                    [
                        'day' => 'monday',
                        'start_time' => '06:00',
                        'end_time' => '14:00',
                        'break_start' => '10:00',
                        'break_end' => '10:30',
                        'working_hours' => 7.5
                    ],
                    [
                        'day' => 'tuesday',
                        'start_time' => '06:00',
                        'end_time' => '14:00',
                        'break_start' => '10:00',
                        'break_end' => '10:30',
                        'working_hours' => 7.5
                    ],
                    [
                        'day' => 'wednesday',
                        'start_time' => '06:00',
                        'end_time' => '14:00',
                        'break_start' => '10:00',
                        'break_end' => '10:30',
                        'working_hours' => 7.5
                    ],
                    [
                        'day' => 'thursday',
                        'start_time' => '06:00',
                        'end_time' => '14:00',
                        'break_start' => '10:00',
                        'break_end' => '10:30',
                        'working_hours' => 7.5
                    ],
                    [
                        'day' => 'friday',
                        'start_time' => '06:00',
                        'end_time' => '14:00',
                        'break_start' => '10:00',
                        'break_end' => '10:30',
                        'working_hours' => 7.5
                    ]
                ]
            ],
            [
                'name' => 'Night Shift',
                'workhour' => [
                    [
                        'day' => 'monday',
                        'start_time' => '18:00',
                        'end_time' => '04:00',
                        'break_start' => '22:00',
                        'break_end' => '22:30',
                        'working_hours' => 9.5
                    ],
                    [
                        'day' => 'tuesday',
                        'start_time' => '18:00',
                        'end_time' => '04:00',
                        'break_start' => '22:00',
                        'break_end' => '22:30',
                        'working_hours' => 9.5
                    ],
                    [
                        'day' => 'wednesday',
                        'start_time' => '18:00',
                        'end_time' => '04:00',
                        'break_start' => '22:00',
                        'break_end' => '22:30',
                        'working_hours' => 9.5
                    ],
                    [
                        'day' => 'thursday',
                        'start_time' => '18:00',
                        'end_time' => '04:00',
                        'break_start' => '22:00',
                        'break_end' => '22:30',
                        'working_hours' => 9.5
                    ],
                    [
                        'day' => 'friday',
                        'start_time' => '18:00',
                        'end_time' => '04:00',
                        'break_start' => '22:00',
                        'break_end' => '22:30',
                        'working_hours' => 9.5
                    ]
                ]
            ],
            [
                'name' => 'Afternoon Shift',
                'workhour' => [
                    [
                        'day' => 'monday',
                        'start_time' => '14:00',
                        'end_time' => '22:00',
                        'break_start' => '18:00',
                        'break_end' => '19:00',
                        'working_hours' => 7
                    ],
                    [
                        'day' => 'tuesday',
                        'start_time' => '14:00',
                        'end_time' => '22:00',
                        'break_start' => '18:00',
                        'break_end' => '19:00',
                        'working_hours' => 7
                    ],
                    [
                        'day' => 'wednesday',
                        'start_time' => '14:00',
                        'end_time' => '22:00',
                        'break_start' => '18:00',
                        'break_end' => '19:00',
                        'working_hours' => 7
                    ],
                    [
                        'day' => 'thursday',
                        'start_time' => '14:00',
                        'end_time' => '22:00',
                        'break_start' => '18:00',
                        'break_end' => '19:00',
                        'working_hours' => 7
                    ],
                    [
                        'day' => 'friday',
                        'start_time' => '14:00',
                        'end_time' => '22:00',
                        'break_start' => '18:00',
                        'break_end' => '19:00',
                        'working_hours' => 7
                    ]
                ]
            ]
        ];

        foreach ($shifts as $shift) {
            Shift::create($shift);
        }
    }
}
