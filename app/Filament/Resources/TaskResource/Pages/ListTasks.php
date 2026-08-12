<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use App\Exports\TasksExport;
use Maatwebsite\Excel\Facades\Excel;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('excel')
                ->label('Export')
                ->icon('heroicon-m-arrow-down-tray')
                ->color('gray')
                ->form([
                    DatePicker::make('start_date')
                        ->default(now()->subMonth()),
                    DatePicker::make('end_date')
                        ->default(now()),
                    Select::make('status')->label('Status')
                        ->options([
                            'To Do' => 'To Do',
                            'In Progress' => 'In Progress',
                            'Done' => 'Done',
                            'Cancelled' => 'Cancelled',
                        ])->default('To Do')

                ])
                ->action(function ($data) {
                    $start_date = $data['start_date'];
                    $end_date = $data['end_date'];
                    $status = $data['status'];
                    return Excel::download(new TasksExport($start_date, $end_date, $status), 'Data Task - '  . date('d-m-Y') . '.xlsx');
                }),
        ];
    }
}
