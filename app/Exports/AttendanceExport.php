<?php

namespace App\Exports;

use App\Models\Attendance;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;

class AttendanceExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    protected $index = 1;

    protected $records;

    public function __construct(Collection $records = null)
    {
        if ($records) {
            $this->records = $records;
        }
    }

    public function collection()
    {

        if ($this->records) {
            return $this->records->sortByDesc('date');
        } else {
            return Attendance::with('employee')
                ->orderBy('date', 'desc')
                ->get();
        }
    }

    public function map($attendance): array
    {
        $workingHours = 'N/A';
        if ($attendance->clock_in && $attendance->clock_out) {
            $clockIn = \Carbon\Carbon::parse($attendance->clock_in);
            $clockOut = \Carbon\Carbon::parse($attendance->clock_out);
            $totalMinutes = abs($clockOut->diffInMinutes($clockIn));
            $hours = intval($totalMinutes / 60);
            $minutes = $totalMinutes % 60;
            $workingHours = "{$hours}h {$minutes}m";
        }

        // make clock_in_status if Hadir change to Present
        if ($attendance->clock_in_status == 'Hadir') {
            $attendance->clock_in_status = 'Present';
        } elseif ($attendance->clock_in_status == 'Terlambat') {
            $attendance->clock_in_status = 'Late';
        } elseif ($attendance->clock_in_status == 'Tidak Hadir') {
            $attendance->clock_in_status = 'Absent';
        } elseif ($attendance->clock_in_status == 'Libur') {
            $attendance->clock_in_status = 'Holiday';
        } elseif ($attendance->clock_in_status == 'Belum Mulai Shift') {
            $attendance->clock_in_status = 'Before Shift';
        } elseif ($attendance->clock_in_status == 'Belum Absen') {
            $attendance->clock_in_status = 'Not Clocked In';
        } elseif ($attendance->clock_in_status == null) {
            $attendance->clock_in_status = 'Not Set';
        } else {
            $attendance->clock_in_status = 'Unknown';
        }

        if ($attendance->clock_out_status == 'Sudah Clock Out') {
            $attendance->clock_out_status = 'Clocked Out';
        } elseif ($attendance->clock_out_status == 'Early Clock Out') {
            $attendance->clock_out_status = 'Early Out';
        } elseif ($attendance->clock_out_status == 'Belum Clock Out') {
            $attendance->clock_out_status = 'Not Out';
        } elseif ($attendance->clock_out_status == 'Libur') {
            $attendance->clock_out_status = 'Holiday';
        } elseif ($attendance->clock_out_status == 'Tidak Hadir') {
            $attendance->clock_out_status = 'Absent';
        } elseif ($attendance->clock_out_status == 'Belum Mulai Shift') {
            $attendance->clock_out_status = 'Before Shift';
        } elseif ($attendance->clock_out_status == 'Belum Absen') {
            $attendance->clock_out_status = 'Not Clocked In';
        } elseif ($attendance->clock_out_status == null) {
            $attendance->clock_out_status = 'Not Set';
        } else {
            $attendance->clock_out_status = 'Unknown';
        }

        return [
            $this->index++,
            $attendance->employee->name ?? 'Unknown',
            $attendance->employee->nik ?? 'N/A',
            $attendance->date,
            $attendance->clock_in?->format('H:i') ?? 'N/A',
            $attendance->clock_out?->format('H:i') ?? 'N/A',
            $attendance->clock_in_status ?? 'N/A',
            $attendance->clock_out_status ?? 'N/A',
            $workingHours,
            $attendance->is_leave ? 'Yes' : 'No',
            $attendance->leave_type ?? 'N/A',
            $attendance->notes ?? ''
        ];
    }

    public function headings(): array
    {
        return [
            '#',
            'Employee',
            'NIK',
            'Date',
            'Clock In',
            'Clock Out',
            'Clock In Status',
            'Clock Out Status',
            'Working Hours',
            'On Leave',
            'Leave Type',
            'Notes'
        ];
    }
}
