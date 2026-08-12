<?php

namespace App\Exports;

use App\Models\Task;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;

class TasksExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    protected $start;
    protected $end;
    protected $status;
    function __construct($start = null, $end = null, $status = null)
    {
        if ($start) {
            $this->start = $start;
        }

        if ($end) {
            $this->end = $end;
        }

        if ($status) {
            $this->status = $status;
        }
    }

    public function collection()
    {
        return Task::with(['customer', 'lead'])
            ->whereDate('created_at', '>=', $this->start)
            ->whereDate('created_at', '<=', $this->end)
            ->where('status', $this->status)
            ->orderBy('created_at', 'desc')->get();
    }

    public function map($task): array
    {
        return [
            $task->id,
            $task->title,
            $task->start_date,
            $task->end_date,
            $task->customer->name ?? "-",
            $task->lead->name ?? "-",
            $task->status,
            $task->prioritas,
            $task->created_at->format('d-m-Y H:i:s'),
        ];
    }

    public function headings(): array
    {
        return [
            '#',
            'Title',
            'Start Date',
            'End Date',
            'Customer',
            'Lead',
            'Status',
            'Priority',
            'Date Created'
        ];
    }
}
