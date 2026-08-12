<?php

namespace App\Filament\Resources\ProposalResource\Pages;

use App\Filament\Resources\ProposalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\DatePicker;
use App\Exports\ProposalsExport;
use Maatwebsite\Excel\Facades\Excel;

class ListProposals extends ListRecords
{
    protected static string $resource = ProposalResource::class;

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
                    return Excel::download(new ProposalsExport($start_date, $end_date), 'Data Proposal - '  . date('d-m-Y') . '.xlsx');
                }),
        ];
    }
}
