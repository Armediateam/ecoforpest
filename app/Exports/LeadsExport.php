<?php

namespace App\Exports;

use App\Models\Lead;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Database\Eloquent\Builder;

class LeadsExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping
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
        return Lead::with(['status', 'source', 'assigned'])
            ->when($this->status, fn(Builder $query) => $query->where('status_id', $this->status))
            ->orderBy('created_at', 'desc')->get();
    }

    public function map($lead): array
    {
        return [
            $lead->id,
            $lead->status->name ?? '-',
            $lead->source->name ?? '-',
            $lead->name,
            $lead->company,
            $lead->lead_value,
            $lead->email,
            $lead->phone,
            $lead->assigned->name ?? '-',
            $lead->is_public ? 'Yes' : 'No',
            $lead->created_at->format('d-m-Y H:i:s'),
        ];
    }

    public function headings(): array
    {
        return [
            '#',
            'Status',
            'Source',
            'Lead Name',
            'Company',
            'Lead Value',
            'Email',
            'Phone',
            'Assigned To',
            'Public',
            'Date Created'
        ];
    }
}
