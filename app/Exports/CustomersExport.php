<?php

namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Database\Eloquent\Builder;

class CustomersExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping
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
        return Customer::with(['lead'])
            ->when($this->status, fn(Builder $query) => $query->where('is_active', $this->status))
            ->orderBy('created_at', 'desc')->get();
    }

    public function map($customer): array
    {
        return [
            $customer->id,
            $customer->name ? $customer->name : '-',
            $customer->company ? $customer->company : '-',
            $customer->email ? $customer->email : '-',
            $customer->phone ? $customer->phone : '-',
            ($customer->lead ? $customer->lead->name : '-'),
            $customer->is_active ? 'Active' : 'Inactive',
            $customer->created_at->format('d-m-Y H:i:s'),
        ];
    }

    public function headings(): array
    {
        return [
            '#',
            'Customer Name',
            'Company',
            'Email',
            'Phone',
            'Lead Source',
            'Status',
            'Date Created'
        ];
    }
}
