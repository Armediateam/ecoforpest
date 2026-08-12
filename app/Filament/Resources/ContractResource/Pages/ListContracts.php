<?php

namespace App\Filament\Resources\ContractResource\Pages;

use App\Filament\Resources\ContractResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\Select;
use App\Exports\ContractsExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\ContractType;

class ListContracts extends ListRecords
{
    protected static string $resource = ContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('excel')
                ->label('Export')
                ->icon('heroicon-m-arrow-down-tray')
                ->color('gray')
                ->form([
                    Select::make('type')
                        ->label('Contract Type')
                        ->options(ContractType::all()->pluck('name', 'id'))
                ])
                ->action(function ($data) {
                    $type = $data['type'];
                    return Excel::download(new ContractsExport($type), 'Data Contract - '  . date('d-m-Y') . '.xlsx');
                }),
        ];
    }
}
