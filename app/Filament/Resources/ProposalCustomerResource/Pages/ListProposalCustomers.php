<?php

namespace App\Filament\Resources\ProposalCustomerResource\Pages;

use App\Filament\Resources\ProposalCustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\DatePicker;
use App\Exports\ProposalCustomersExport;
use Maatwebsite\Excel\Facades\Excel;

class ListProposalCustomers extends ListRecords
{
    protected static string $resource = ProposalCustomerResource::class;

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

                ])
                ->action(function ($data) {
                    $start_date = $data['start_date'];
                    $end_date = $data['end_date'];
                    return Excel::download(new ProposalCustomersExport($start_date, $end_date), 'Data Proposal Customer - '  . date('d-m-Y') . '.xlsx');
                }),
        ];
    }
}
