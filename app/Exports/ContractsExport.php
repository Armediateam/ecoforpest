<?php

namespace App\Exports;

use App\Models\Contract;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Database\Eloquent\Builder;

class ContractsExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    protected $type;
    function __construct($type = null)
    {
        if ($type) {
            $this->type = $type;
        }
    }

    public function collection()
    {
        return Contract::with(['customer', 'contractType'])
            ->when($this->type, fn(Builder $query) => $query->where('contract_type_id', $this->type))
            ->orderBy('created_at', 'desc')->get();
    }

    public function map($contract): array
    {
        return [
            $contract->id,
            $contract->customer->name,
            $contract->subject,
            $contract->contract_value,
            $contract->contractType->name,
            $contract->start_date,
            $contract->end_date,
            $contract->created_at->format('d-m-Y H:i:s'),
        ];
    }

    public function headings(): array
    {
        return [
            '#',
            'Customer Name',
            'Subject',
            'Contract Value',
            'Contract Type',
            'Start Date',
            'End Date',
            'Date Created'
        ];
    }
}
