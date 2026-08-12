<?php

namespace App\Filament\Widgets;

use App\Models\Payroll;
use App\Models\Overtime;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class OvertimeCostTrackingChart extends ChartWidget
{
    protected static ?string $heading = 'Overtime Cost Tracking';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $months = [];
        $overtimeHours = [];
        $overtimeCosts = [];

        // Get data for last 6 months
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $startOfMonth = $month->copy()->startOfMonth();
            $endOfMonth = $month->copy()->endOfMonth();

            $months[] = $month->format('M Y');

            // Total overtime hours
            $totalHours = Overtime::whereBetween('date', [$startOfMonth, $endOfMonth])
                ->where('status', 'approved')
                ->whereNull('deleted_at')
                ->sum('duration_hour');

            // Total overtime costs from payroll
            $monthlyPayrolls = Payroll::whereBetween('period_start_date', [$startOfMonth, $endOfMonth])
                ->whereNull('deleted_at')
                ->get();

            $totalCost = $monthlyPayrolls->sum(function ($payroll) {
                $income = $payroll->employee_income;
                return $income['overtime'] ?? 0;
            });

            $overtimeHours[] = $totalHours;
            $overtimeCosts[] = $totalCost / 1000000; // Convert to millions for readability
        }

        return [
            'datasets' => [
                [
                    'label' => 'Overtime Hours',
                    'data' => $overtimeHours,
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'borderColor' => 'rgb(245, 158, 11)',
                    'borderWidth' => 2,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Overtime Cost (Millions Rp)',
                    'data' => $overtimeCosts,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'borderWidth' => 2,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Hours',
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Cost (Millions Rp)',
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Month',
                    ],
                ],
            ],
        ];
    }

    /**
     * Get current month overtime statistics
     */
    public function getDescription(): ?string
    {
        $currentMonth = Carbon::now();
        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        $totalHours = Overtime::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->where('status', 'approved')
            ->whereNull('deleted_at')
            ->sum('duration_hour');

        $monthlyPayrolls = Payroll::whereBetween('period_start_date', [$startOfMonth, $endOfMonth])
            ->whereNull('deleted_at')
            ->get();

        $totalCost = $monthlyPayrolls->sum(function ($payroll) {
            $income = $payroll->employee_income;
            return $income['overtime'] ?? 0;
        });

        $avgCostPerHour = $totalHours > 0 ? $totalCost / $totalHours : 0;

        return "Current month: {$totalHours} hours, Rp " . number_format($totalCost, 0, ',', '.') . " total cost, Rp " . number_format($avgCostPerHour, 0, ',', '.') . " per hour";
    }
}
