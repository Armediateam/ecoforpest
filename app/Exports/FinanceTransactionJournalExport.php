<?php

namespace App\Exports;

use App\Models\FinanceTransaction;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class FinanceTransactionJournalExport implements FromView, ShouldAutoSize
{
    protected $transactions;
    protected $filters;

    public function __construct($transactions, $filters = [])
    {
        $this->transactions = $transactions;
        $this->filters = $filters;
    }

    public function view(): View
    {
        // Baris saldo awal
        $openingBalance = 0;
        if (!empty($this->transactions) && isset($this->filters['from'])) {
            // Hitung saldo sebelum periode jika ada transaksi sebelumnya
            $firstDate = $this->filters['from'];
            $openingBalance = FinanceTransaction::whereDate('date', '<', $firstDate)
                ->when(isset($this->filters['category_id']), function ($q) {
                    $q->where('finance_category_id', $this->filters['category_id']);
                })
                ->get()
                ->reduce(function ($carry, $trx) {
                    return $carry + ($trx->type === 'income' ? $trx->amount : -$trx->amount);
                }, 0);
        }
        $saldo = $openingBalance;
        $rows = [];
        $no = 1;
        $rows[] = [
            'no' => '',
            'date' => $this->filters['from'] ?? '',
            'description' => 'Opening Balance',
            'income' => '',
            'expense' => '',
            'balance' => $saldo,
        ];
        $totalIncome = 0;
        $totalExpense = 0;
        foreach ($this->transactions as $trx) {
            $income = $trx->type === 'income' ? $trx->amount : 0;
            $expense = $trx->type === 'expense' ? $trx->amount : 0;
            $saldo += $income - $expense;
            $totalIncome += $income;
            $totalExpense += $expense;
            $rows[] = [
                'no' => $no++,
                'date' => $trx->date,
                'description' => $trx->description,
                'income' => $income,
                'expense' => $expense,
                'balance' => $saldo,
            ];
        }
        return view('exports.finance-journal', [
            'rows' => $rows,
            'filters' => $this->filters,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
        ]);
    }
}
