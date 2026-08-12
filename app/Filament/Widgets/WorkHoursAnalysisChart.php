<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class WorkHoursAnalysisChart extends ChartWidget
{
    protected static ?string $heading = 'Work Hours Analysis';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $months = [];
        $actualHours = [];
        $expectedHours = [];

        // Expected work hours per month (22 work days * 8 hours)
        $expectedMonthlyHours = 22 * 8;

        // Get data for last 6 months
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $startOfMonth = $month->copy()->startOfMonth();
            $endOfMonth = $month->copy()->endOfMonth();

            $months[] = $month->format('M Y');

            // Total actual work hours from attendance
            $totalActualHours = Attendance::whereBetween('date', [$startOfMonth, $endOfMonth])
                ->whereIn('clock_in_status', ['Hadir', 'Terlambat'])
                ->whereNull('deleted_at')
                ->sum('workhours') ?? 0;

            // Average actual hours per employee
            $activeEmployees = Employee::where('status', 'active')->count();
            $avgActualHours = $activeEmployees > 0 ? $totalActualHours / $activeEmployees : 0;

            $actualHours[] = round($avgActualHours, 1);
            $expectedHours[] = $expectedMonthlyHours;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Actual Average Hours',
                    'data' => $actualHours,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
                [
                    'label' => 'Expected Hours',
                    'data' => $expectedHours,
                    'backgroundColor' => 'rgba(156, 163, 175, 0.1)',
                    'borderColor' => 'rgb(156, 163, 175)',
                    'borderWidth' => 2,
                    'borderDash' => [5, 5],
                    'fill' => false,
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
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Hours per Employee',
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
     * Get current month work hours statistics
     */
    public function getDescription(): ?string
    {
        $currentMonth = Carbon::now();
        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        $totalActualHours = Attendance::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->whereIn('clock_in_status', ['Hadir', 'Terlambat'])
            ->whereNull('deleted_at')
            ->sum('workhours') ?? 0;

        $activeEmployees = Employee::where('status', 'active')->count();
        $avgActualHours = $activeEmployees > 0 ? round($totalActualHours / $activeEmployees, 1) : 0;
        
        $expectedHours = 22 * 8; // 22 work days * 8 hours
        $efficiency = $avgActualHours > 0 ? round(($avgActualHours / $expectedHours) * 100, 1) : 0;

        return "Current month: {$avgActualHours} avg hours per employee, {$efficiency}% efficiency vs expected {$expectedHours} hours";
    }
}
