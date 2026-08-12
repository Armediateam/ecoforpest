<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\Select;
use App\Exports\CustomersExport;
use Maatwebsite\Excel\Facades\Excel;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

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
                        ->label('Status')
                        ->options([
                            1 => 'Active',
                            0 => 'Inactive',
                        ])->default(1)
                ])
                ->action(function ($data) {
                    $status = $data['status'];
                    return Excel::download(new CustomersExport($status), 'Data Customer - '  . date('d-m-Y') . '.xlsx');
                }),
        ];
    }
}
