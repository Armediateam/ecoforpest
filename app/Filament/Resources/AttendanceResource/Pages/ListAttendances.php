<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Exports\AttendanceExport;
use App\Filament\Resources\AttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Attendance;
use Maatwebsite\Excel\Facades\Excel;

class ListAttendances extends ListRecords
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus')
                ->label('New Attendance'),

            Actions\Action::make('exportAll')
                ->label('Export All')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                // ->action(function () {
                //     $attendances = Attendance::with('employee')->get();

                //     return response()->streamDownload(function () use ($attendances) {
                //         echo "Employee,NIK,Date,Clock In,Clock Out,Clock In Status,Clock Out Status,Working Hours,On Leave,Leave Type,Notes\n";

                //         foreach ($attendances as $attendance) {
                //             $workingHours = 'N/A';
                //             if ($attendance->clock_in && $attendance->clock_out) {
                //                 $clockIn = \Carbon\Carbon::parse($attendance->clock_in);
                //                 $clockOut = \Carbon\Carbon::parse($attendance->clock_out);
                //                 $totalMinutes = $clockOut->diffInMinutes($clockIn);
                //                 $hours = intval($totalMinutes / 60);
                //                 $minutes = $totalMinutes % 60;
                //                 $workingHours = "{$hours}h {$minutes}m";
                //             }

                //             echo sprintf(
                //                 "\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                //                 $attendance->employee->name ?? 'Unknown',
                //                 $attendance->employee->nik ?? 'N/A',
                //                 $attendance->date,
                //                 $attendance->clock_in?->format('H:i') ?? 'N/A',
                //                 $attendance->clock_out?->format('H:i') ?? 'N/A',
                //                 $attendance->clock_in_status ?? 'N/A',
                //                 $attendance->clock_out_status ?? 'N/A',
                //                 $workingHours,
                //                 $attendance->is_leave ? 'Yes' : 'No',
                //                 $attendance->leave_type ?? 'N/A',
                //                 str_replace('"', '""', $attendance->notes ?? '')
                //             );
                //         }
                //     }, 'all-attendance-records.csv');
                // })

                ->action(function () {
                    return Excel::download(new AttendanceExport(), 'Data Attendance - '  . date('d-m-Y') . '.xlsx');
                })
                ->requiresConfirmation()
                ->modalDescription('This will export all attendance records to a XLSX file.'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Records')
                ->badge(Attendance::count()),

            'today' => Tab::make('Today')
                ->badge(Attendance::whereDate('date', today())->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->whereDate('date', today())),

            'this_week' => Tab::make('This Week')
                ->badge(Attendance::whereBetween('date', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->whereBetween('date', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])),

            'this_month' => Tab::make('This Month')
                ->badge(Attendance::whereMonth('date', now()->month)
                    ->whereYear('date', now()->year)->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->whereMonth('date', now()->month)
                    ->whereYear('date', now()->year)),

            'present' => Tab::make('Present')
                ->badge(Attendance::where('clock_in_status', 'Hadir')->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->where('clock_in_status', 'Hadir')),

            'late' => Tab::make('Late')
                ->badge(Attendance::where('clock_in_status', 'Terlambat')->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->where('clock_in_status', 'Terlambat')),

            'absent' => Tab::make('Absent')
                ->badge(Attendance::where('clock_in_status', 'Tidak Hadir')->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->where('clock_in_status', 'Tidak Hadir')),

            'on_leave' => Tab::make('On Leave')
                ->badge(Attendance::where('is_leave', true)->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->where('is_leave', true)),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AttendanceResource\Widgets\AttendanceStatsWidget::class,
        ];
    }
}
