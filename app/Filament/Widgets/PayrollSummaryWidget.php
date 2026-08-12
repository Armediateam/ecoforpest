<?php

namespace App\Filament\Widgets;

use App\Models\Payroll;
use App\Models\Attendance;
use App\Models\Overtime;
use App\Models\Employee;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PayrollSummaryWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $currentMonth = Carbon::now();
        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        // Monthly Payroll Statistics
        $monthlyPayrolls = Payroll::whereBetween('period_start_date', [$startOfMonth, $endOfMonth])
            ->whereNull('deleted_at')
            ->get();

        $totalPayrollCost = $monthlyPayrolls->sum('final_salary');
        $totalEmployeesProcessed = $monthlyPayrolls->count();
        $averageSalary = $totalEmployeesProcessed > 0 ? $totalPayrollCost / $totalEmployeesProcessed : 0;

        // Attendance vs Payroll Comparison
        $totalAttendances = Attendance::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->whereNull('deleted_at')
            ->count();

        $totalOvertimeHours = Overtime::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->where('status', 'approved')
            ->whereNull('deleted_at')
            ->sum('duration_hour');

        $totalOvertimeCost = $monthlyPayrolls->sum(function ($payroll) {
            $income = $payroll->employee_income;
            return $income['overtime'] ?? 0;
        });

        // Active Employees
        $activeEmployees = Employee::where('status', 'active')->count();
        $payrollCoverage = $activeEmployees > 0 ? ($totalEmployeesProcessed / $activeEmployees) * 100 : 0;

        return [
            Stat::make('Monthly Payroll Cost', 'Rp ' . number_format($totalPayrollCost, 0, ',', '.'))
                ->description('Total payroll for ' . $currentMonth->format('F Y'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart($this->getPayrollTrend()),

            Stat::make('Employees Processed', $totalEmployeesProcessed)
                ->description($payrollCoverage . '% of active employees')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('Average Salary', 'Rp ' . number_format($averageSalary, 0, ',', '.'))
                ->description('Per employee this month')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('warning'),

            Stat::make('Overtime Hours', number_format($totalOvertimeHours, 1) . ' hrs')
                ->description('Cost: Rp ' . number_format($totalOvertimeCost, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-clock')
                ->color('danger'),

            Stat::make('Attendance Records', number_format($totalAttendances))
                ->description('Total attendance this month')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('gray'),

            // Stat::make('Payroll Coverage', number_format($payrollCoverage, 1) . '%')
            //     ->description('Of active employees processed')
            //     ->descriptionIcon('heroicon-m-chart-pie')
            //     ->color($payrollCoverage >= 90 ? 'success' : ($payrollCoverage >= 70 ? 'warning' : 'danger')),
        ];
    }

    /**
     * Get payroll trend for the last 6 months
     */
    private function getPayrollTrend(): array
    {
        $trends = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $startOfMonth = $month->copy()->startOfMonth();
            $endOfMonth = $month->copy()->endOfMonth();

            $monthlyTotal = Payroll::whereBetween('period_start_date', [$startOfMonth, $endOfMonth])
                ->whereNull('deleted_at')
                ->sum('final_salary');

            $trends[] = $monthlyTotal / 1000000; // Convert to millions for chart readability
        }

        return $trends;
    }

    /**
     * Refresh widget every 5 minutes
     */
    protected static ?string $pollingInterval = '300s';

    /**
     * Widget can be cached for 5 minutes
     */
    protected static ?string $cacheKey = 'payroll-summary-widget';
}
