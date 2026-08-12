<?php

namespace App\Exports;

use App\Models\WorkOrder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Database\Eloquent\Builder;

class WorkOrdersExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return WorkOrder::with(['service', 'lead', 'customer', 'assigned'])->get();
    }

    public function map($wo): array
    {
        return [
            $wo->id,
            $wo->service->name ?? '-',
            $wo->status,
            $wo->total,
            $wo->detail_work,
            $wo->related,
            $wo->lead->name ?? '-',
            $wo->customer->name ?? '-',
            $wo->work_date,
            $wo->work_time,
            $wo->assigned->name ?? '-',
            $wo->created_at->format('d-m-Y H:i:s'),
        ];
    }

    public function headings(): array
    {
        return [
            '#',
            'Service',
            'Status',
            'Total',
            'Detail Work',
            'Related',
            'Lead',
            'Customer',
            'Work Date',
            'Work Time',
            'Assigned',
            'Date Created'
        ];
    }
}
