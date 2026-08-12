<?php

namespace App\Filament\Widgets;

use App\Models\Payroll;
use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class AttendancePayrollComparisonChart extends ChartWidget
{
    protected static ?string $heading = 'Attendance vs Payroll Comparison';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $months = [];
        $attendanceData = [];
        $payrollData = [];

        // Get data for last 6 months
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $startOfMonth = $month->copy()->startOfMonth();
            $endOfMonth = $month->copy()->endOfMonth();

            $months[] = $month->format('M Y');

            // Count unique employees with attendance
            $attendanceCount = Attendance::whereBetween('date', [$startOfMonth, $endOfMonth])
                ->whereNull('deleted_at')
                ->distinct('employee_id')
                ->count('employee_id');

            // Count employees with payroll
            $payrollCount = Payroll::whereBetween('period_start_date', [$startOfMonth, $endOfMonth])
                ->whereNull('deleted_at')
                ->count();

            $attendanceData[] = $attendanceCount;
            $payrollData[] = $payrollCount;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Employees with Attendance',
                    'data' => $attendanceData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
                [
                    'label' => 'Employees with Payroll',
                    'data' => $payrollData,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'borderWidth' => 2,
                    'fill' => true,
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
                        'text' => 'Number of Employees',
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
     * Get additional stats for the widget
     */
    public function getDescription(): ?string
    {
        $currentMonth = Carbon::now();
        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        $attendanceCount = Attendance::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->whereNull('deleted_at')
            ->distinct('employee_id')
            ->count('employee_id');

        $payrollCount = Payroll::whereBetween('period_start_date', [$startOfMonth, $endOfMonth])
            ->whereNull('deleted_at')
            ->count();

        $activeEmployees = Employee::where('status', 'active')->count();

        $attendanceCoverage = $activeEmployees > 0 ? round(($attendanceCount / $activeEmployees) * 100, 1) : 0;
        $payrollCoverage = $activeEmployees > 0 ? round(($payrollCount / $activeEmployees) * 100, 1) : 0;

        return "Current month: {$attendanceCoverage}% attendance coverage, {$payrollCoverage}% payroll coverage of {$activeEmployees} active employees";
    }
}
