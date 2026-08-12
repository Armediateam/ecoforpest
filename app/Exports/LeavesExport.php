<?php

namespace App\Exports;

use App\Models\Leave;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Database\Eloquent\Builder;

class LeavesExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    protected $status;
    function __construct($status = null)
    {
        if ($status) {
            $this->status = $status;
        }
    }

    public function collection()
    {
        return Leave::with(['employee', 'approvedBy'])->when($this->status, fn(Builder $query) => $query->where('status', $this->status))
            ->orderBy('created_at', 'desc')->get();
    }

    public function map($leave): array
    {
        return [
            $leave->id,
            $leave->employee->name ?? 'N/A',
            $leave->leave_type ?? 'N/A',
            $leave->start_date ?? 'N/A',
            $leave->end_date ?? 'N/A',
            $leave->reason ?? 'N/A',
            ucfirst($leave->status ?? 'N/A'),
            $leave->request_date ?? 'N/A',
            $leave->approvedBy?->name ?? 'N/A',
            $leave->approved_at ?? 'N/A',
            $leave->created_at->format('d-m-Y H:i:s'),
        ];
    }

    public function headings(): array
    {
        return [
            '#',
            'Employee',
            'Leave Type',
            'Start Date',
            'End Date',
            'Reason',
            'Status',
            'Request Date',
            'Approved By',
            'Approved At',
            'Date Created'
        ];
    }
}
