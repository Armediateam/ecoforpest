<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FinanceCategory;
use App\Models\FinanceTransaction;
use Carbon\Carbon;

class FinanceDummySeeder extends Seeder
{
    public function run(): void
    {
        // Finance Categories
        $categories = [
            ['name' => 'Operational', 'description' => 'Biaya operasional perusahaan'],
            ['name' => 'Marketing', 'description' => 'Biaya promosi dan marketing'],
            ['name' => 'Gaji', 'description' => 'Pengeluaran gaji karyawan'],
            ['name' => 'Pendapatan', 'description' => 'Pemasukan dari penjualan'],
        ];
        foreach ($categories as $cat) {
            FinanceCategory::create($cat);
        }

        // Finance Transactions
        $catIds = FinanceCategory::pluck('id')->toArray();
        $dummyTransactions = [
            [
                'finance_category_id' => $catIds[0] ?? 1,
                'amount' => 1500000,
                'description' => 'Pembelian ATK',
                'date' => Carbon::now()->subDays(5),
                'user_id' => 1,
                    'type' => 'expense',
                ],
            [
                'finance_category_id' => $catIds[1] ?? 2,
                'amount' => 2000000,
                'description' => 'Iklan Facebook',
                'date' => Carbon::now()->subDays(3),
                'user_id' => 1,
                    'type' => 'expense',
                ],
            [
                'finance_category_id' => $catIds[2] ?? 3,
                'amount' => 5000000,
                'description' => 'Gaji Bulanan',
                'date' => Carbon::now()->subDays(1),
                'user_id' => 1,
                    'type' => 'expense',
                ],
            [
                'finance_category_id' => $catIds[3] ?? 4,
                'amount' => 10000000,
                'description' => 'Penjualan Paket Layanan',
                'date' => Carbon::now(),
                'user_id' => 1,
                    'type' => 'income',
                ],
        ];
        foreach ($dummyTransactions as $trx) {
            FinanceTransaction::create($trx);
        }
    }
}
