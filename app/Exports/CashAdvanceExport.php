<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CashAdvanceExport implements FromView, ShouldAutoSize
{
    protected $cashAdvances;
    protected $filters;

    public function __construct($cashAdvances, $filters = [])
    {
        $this->cashAdvances = $cashAdvances;
        $this->filters = $filters;
    }

    public function view(): View
    {
        $rows = [];
        $no = 1;
        foreach ($this->cashAdvances as $kasbon) {
            $rows[] = [
                'no' => $no++,
                'date' => $kasbon->date,
                'description' => $kasbon->description,
                'employee' => $kasbon->employee?->name ?? '-',
                'amount' => $kasbon->amount,
                'status' => ucfirst($kasbon->status),
                'paid_at' => $kasbon->paid_at,
                'reference' => $kasbon->reference,
            ];
        }
        return view('exports.cash-advance', [
            'rows' => $rows,
            'filters' => $this->filters,
        ]);
    }
}
