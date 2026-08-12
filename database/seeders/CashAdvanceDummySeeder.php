<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CashAdvance;
use App\Models\User;
use App\Models\Employee;
use Carbon\Carbon;

class CashAdvanceDummySeeder extends Seeder
{
    public function run(): void
    {
        // Ambil user dan employee pertama
        $userId = User::first()?->id ?? 1;
        $employeeId = Employee::first()?->id ?? 1;

        $dummyCashAdvances = [
            [
                'date' => Carbon::now()->subDays(5),
                'employee_id' => $employeeId,
                'user_id' => $userId,
                'description' => 'Kasbon untuk perjalanan dinas',
                'amount' => 1000000,
            ],
            [
                'date' => Carbon::now()->subDays(3),
                'employee_id' => $employeeId,
                'user_id' => $userId,
                'description' => 'Kasbon pembelian alat kantor',
                'amount' => 500000,
            ],
            [
                'date' => Carbon::now()->subDays(1),
                'employee_id' => $employeeId,
                'user_id' => $userId,
                'description' => 'Kasbon makan siang tim',
                'amount' => 250000,
            ],
        ];

        foreach ($dummyCashAdvances as $ca) {
            CashAdvance::create($ca);
        }
    }
}
