<?php

namespace App\Exports;

use App\Models\CashAdvance;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CashAdvanceReportExport implements FromView, ShouldAutoSize
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
        $totalAmount = $this->cashAdvances->sum('amount');
        return view('exports.cash-advance-report', [
            'cashAdvances' => $this->cashAdvances,
            'filters' => $this->filters,
            'totalAmount' => $totalAmount,
        ]);
    }
}
