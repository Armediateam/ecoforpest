<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\Select;
use App\Exports\EmployeesExport;
use Maatwebsite\Excel\Facades\Excel;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('excel')
                ->label('Export')
                ->icon('heroicon-m-arrow-down-tray')
                ->color('gray')
                ->form([
                    Select::make('status')
                        ->label('Employee Status')
                        ->options([
                            'active' => 'Active',
                            'inactive' => 'Inactive',
                            'on_leave' => 'On Leave',
                            'terminated' => 'Terminated',
                        ])
                        ->default('active')
                ])
                ->action(function ($data) {
                    $status = $data['status'];
                    return Excel::download(new EmployeesExport($status), 'Data Employee - '  . date('d-m-Y') . '.xlsx');
                }),
        ];
    }
}
