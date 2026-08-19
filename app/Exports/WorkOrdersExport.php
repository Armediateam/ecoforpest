<?php

namespace App\Exports;

use App\Models\WorkOrder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;

class WorkOrdersExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping
{
    public function __construct(private array $filters = [])
    {
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return WorkOrder::with(['service', 'lead', 'customer', 'assigned'])
            ->when($this->filterValues('status'), fn($query, $values) => $query->whereIn('status', $values))
            ->when($this->filterValues('service'), fn($query, $values) => $query->whereIn('service_id', $values))
            ->when($this->filterValue('related'), fn($query, $value) => $query->where('related', $value))
            ->when($this->filterValues('customer'), fn($query, $values) => $query->whereIn('customer_id', $values))
            ->when($this->filterValues('lead'), fn($query, $values) => $query->whereIn('lead_id', $values))
            ->when($this->filterValue('assigned'), fn($query, $value) => $query->where('assigned_id', $value))
            ->when($this->filterValues('helpers'), function ($query, $values) {
                $query->whereHas('helpers', fn($helpersQuery) => $helpersQuery->whereIn('employees.id', $values));
            })
            ->when($this->filterValue('work_date_range', 'from'), fn($query, $date) => $query->whereDate('work_date', '>=', $date))
            ->when($this->filterValue('work_date_range', 'until'), fn($query, $date) => $query->whereDate('work_date', '<=', $date))
            ->when($this->filterValue('amount_range', 'min_amount'), fn($query, $amount) => $query->where('total', '>=', $amount))
            ->when($this->filterValue('amount_range', 'max_amount'), fn($query, $amount) => $query->where('total', '<=', $amount))
            ->when($this->filterBoolean('is_recuring') !== null, function ($query) {
                $query->where('is_recuring', $this->filterBoolean('is_recuring'));
            })
            ->get();
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

    private function filterValue(string $filter, string $key = 'value'): mixed
    {
        $value = $this->filters[$filter][$key] ?? null;

        return $value === '' ? null : $value;
    }

    private function filterValues(string $filter): array
    {
        return array_values(array_filter((array) ($this->filters[$filter]['values'] ?? []), fn($value) => $value !== null && $value !== ''));
    }

    private function filterBoolean(string $filter): ?bool
    {
        $value = $this->filterValue($filter);

        if ($value === null || $value === 'blank') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
}
