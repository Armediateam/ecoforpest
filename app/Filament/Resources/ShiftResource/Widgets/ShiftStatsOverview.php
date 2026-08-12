<?php

namespace App\Filament\Resources\ShiftResource\Widgets;

use App\Models\Shift;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Department;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ShiftStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalShifts = Shift::count();
        $totalEmployees = Employee::count();

        $employeesWithOverride = Employee::whereNotNull('shift_id')->count();
        $employeesWithoutShift = Employee::whereNull('shift_id')
            ->whereDoesntHave('position', function ($query) {
                $query->whereNotNull('default_shift_id')
                    ->orWhereHas('department', function ($subQuery) {
                        $subQuery->whereNotNull('default_shift_id');
                    });
            })->count();

        $positionsWithShift = Position::whereNotNull('default_shift_id')->count();
        $departmentsWithShift = Department::whereNotNull('default_shift_id')->count();

        return [
            Stat::make('Total Shifts', $totalShifts)
                ->description('Available shift configurations')
                ->descriptionIcon('heroicon-m-clock')
                ->color('primary'),

            Stat::make('Employee Overrides', $employeesWithOverride)
                ->description("Out of {$totalEmployees} total employees")
                ->descriptionIcon('heroicon-m-user')
                ->color('warning'),

            Stat::make('Positions with Default Shift', $positionsWithShift)
                ->description('Positions configured with default shift')
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('info'),

            Stat::make('Departments with Default Shift', $departmentsWithShift)
                ->description('Departments configured with default shift')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('success'),

            Stat::make('Employees without Shift', $employeesWithoutShift)
                ->description('Employees not assigned to any shift')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($employeesWithoutShift > 0 ? 'danger' : 'gray'),
        ];
    }
}
