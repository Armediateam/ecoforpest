<?php

namespace App\Filament\Resources\AttendanceResource\Widgets;

use App\Models\Attendance;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class AttendanceStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $today = Carbon::today();
        $thisWeek = [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()];
        $thisMonth = Carbon::now();

        // Today's stats
        $todayTotal = Attendance::whereDate('date', $today)->count();
        $todayPresent = Attendance::whereDate('date', $today)->where('clock_in_status', 'Hadir')->count();
        $todayLate = Attendance::whereDate('date', $today)->where('clock_in_status', 'Terlambat')->count();
        $todayAbsent = Attendance::whereDate('date', $today)->where('clock_in_status', 'Tidak Hadir')->count();

        // This week's stats
        $weekTotal = Attendance::whereBetween('date', $thisWeek)->count();
        $weekPresent = Attendance::whereBetween('date', $thisWeek)->where('clock_in_status', 'Hadir')->count();

        // This month's stats
        $monthTotal = Attendance::whereMonth('date', $thisMonth->month)
            ->whereYear('date', $thisMonth->year)
            ->count();
        $monthOnLeave = Attendance::whereMonth('date', $thisMonth->month)
            ->whereYear('date', $thisMonth->year)
            ->where('is_leave', true)
            ->count();

        // Calculate percentages
        $todayPresentPercentage = $todayTotal > 0 ? round(($todayPresent / $todayTotal) * 100, 1) : 0;
        $weekPresentPercentage = $weekTotal > 0 ? round(($weekPresent / $weekTotal) * 100, 1) : 0;

        return [
            Stat::make('Today\'s Attendance', $todayTotal)
                ->description("{$todayPresent} present, {$todayLate} late, {$todayAbsent} absent")
                ->descriptionIcon('heroicon-m-calendar-days')
                ->chart([7, 12, 18, 22, 15, 24, $todayTotal])
                ->color($todayPresentPercentage >= 80 ? 'success' : ($todayPresentPercentage >= 60 ? 'warning' : 'danger')),

            Stat::make('This Week', $weekTotal)
                ->description("{$weekPresentPercentage}% attendance rate")
                ->descriptionIcon('heroicon-m-chart-bar')
                ->chart([12, 18, 22, 15, 24, 32, $weekTotal])
                ->color($weekPresentPercentage >= 85 ? 'success' : ($weekPresentPercentage >= 70 ? 'warning' : 'danger')),

            Stat::make('This Month', $monthTotal)
                ->description("{$monthOnLeave} employees on leave")
                ->descriptionIcon('heroicon-m-user-group')
                ->chart([45, 52, 48, 61, 58, 63, $monthTotal])
                ->color('info'),

            Stat::make('Late Arrivals Today', $todayLate)
                ->description($todayLate > 0 ? 'Requires attention' : 'All on time!')
                ->descriptionIcon($todayLate > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->chart([2, 5, 3, 8, 4, 6, $todayLate])
                ->color($todayLate === 0 ? 'success' : ($todayLate <= 3 ? 'warning' : 'danger')),
        ];
    }
}
