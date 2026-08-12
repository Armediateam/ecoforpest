<?php

namespace App\Exports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Database\Eloquent\Builder;

class EmployeesExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping
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
        return Employee::with(['position'])->when($this->status, fn(Builder $query) => $query->where('status', $this->status))
            ->orderBy('created_at', 'desc')->get();
    }

    public function map($employee): array
    {
        return [
            $employee->id,
            $employee->nik,
            $employee->name,
            $employee->gender,
            $employee->birth_date,
            $employee->email,
            $employee->phone,
            $employee->join_date,
            $employee->position->title,
            $employee->status,
            $employee->created_at->format('d-m-Y H:i:s'),
        ];
    }

    public function headings(): array
    {
        return [
            '#',
            'NIK',
            'Name',
            'Gender',
            'Birth Date',
            'Email',
            'Phone',
            'Join Date',
            'Position',
            'Status',
            'Date Created'
        ];
    }
}
