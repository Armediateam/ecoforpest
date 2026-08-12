<?php

namespace App\Exports;

use App\Models\Overtime;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Database\Eloquent\Builder;

class OvertimesExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping
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
        return Overtime::with(['employee', 'approvedBy'])->when($this->status, fn(Builder $query) => $query->where('status', $this->status))
            ->orderBy('created_at', 'desc')->get();
    }

    public function map($overtime): array
    {
        return [
            $overtime->id,
            $overtime->employee->name,
            $overtime->date,
            $overtime->start_time,
            $overtime->end_time,
            $overtime->duration_hour,
            $overtime->status,
            $overtime->request_date,
            $overtime->reason ?? 'N/A',
            $overtime->approvedBy?->name ?? 'N/A',
            $overtime->approved_at ?: 'N/A',
            $overtime->created_at->format('d-m-Y H:i:s'),
        ];
    }

    public function headings(): array
    {
        return [
            '#',
            'Employee',
            'Date',
            'Start Time',
            'End Time',
            'Duration Hour',
            'Status',
            'Request Date',
            'Reason',
            'Approver',
            'Approved At',
            'Date Created'
        ];
    }
}
