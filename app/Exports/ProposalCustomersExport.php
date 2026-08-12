<?php

namespace App\Exports;

use App\Models\ProposalCustomer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProposalCustomersExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    protected $start;
    protected $end;
    function __construct($start = null, $end = null)
    {
        if ($start) {
            $this->start = $start;
        }

        if ($end) {
            $this->end = $end;
        }
    }

    public function collection()
    {
        return ProposalCustomer::with(['lead'])
            ->where('customer_id', '!=', NULL)
            ->whereDate('created_at', '>=', $this->start)
            ->whereDate('created_at', '<=', $this->end)
            ->orderBy('created_at', 'desc')->get();
    }

    public function map($proposal): array
    {
        return [
            $proposal->id,
            $proposal->subject,
            $proposal->related,
            $proposal->customer->name ?? "-",
            $proposal->to,
            $proposal->email,
            $proposal->phone,
            $proposal->date,
            $proposal->open_till,
            $proposal->status,
            $proposal->created_at->format('d-m-Y H:i:s'),
        ];
    }

    public function headings(): array
    {
        return [
            '#',
            'Subject',
            'Type',
            'Customer',
            'Customer Contact',
            'Email',
            'Phone',
            'Proposal Date',
            'Valid Until',
            'Status',
            'Date Created'
        ];
    }
}
