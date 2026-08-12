<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;
use App\Models\WorkOrder;
use App\Models\Invoice;

class IncomeSummaryWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $date = Carbon::now();

        $sumTotalMonthly = Invoice::whereYear('created_at', $date->year)
            ->whereMonth('created_at', $date->month)
            ->where('status', 'Paid')
            ->sum('total');

        $sumTotalAll = Invoice::where('status', 'Paid')->sum('total');

        $countAll = WorkOrder::count();

        return [
            Stat::make('Total Work Order', $countAll)
                ->description('Total All Work Orders')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('info'),
            Stat::make('Monthly Income', 'Rp ' . number_format($sumTotalMonthly, 0, ',', '.'))
                ->description('Total Income for ' . $date->format('F Y'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            Stat::make('All Income', 'Rp ' . number_format($sumTotalAll, 0, ',', '.'))
                ->description('Total All Income')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
        ];
    }
}
