<?php

namespace App\Exports;

use App\Models\Permit;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Database\Eloquent\Builder;

class PermitsExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping
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
        return Permit::with(['employee', 'approvedBy'])->when($this->status, fn(Builder $query) => $query->where('status', $this->status))
            ->orderBy('created_at', 'desc')->get();
    }

    public function map($permit): array
    {
        return [
            $permit->id,
            $permit->employee->name,
            $permit->date,
            $permit->start_time,
            $permit->end_time,
            $permit->status,
            $permit->request_date,
            $permit->approvedBy?->name ?? 'N/A',
            $permit->approved_at ?: 'N/A',
            $permit->created_at->format('d-m-Y H:i:s'),
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
            'Status',
            'Request Date',
            'Approver',
            'Approved At',
            'Date Created'
        ];
    }
}
