<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Models\Status;
use Filament\Forms\Components\Select;
use App\Exports\LeadsExport;
use Maatwebsite\Excel\Facades\Excel;

class ListLeads extends ListRecords
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('excel')
                ->label('Export')
                ->icon('heroicon-m-arrow-down-tray')
                ->color('gray')
                ->form([
                    Select::make('status')->label('Status')
                        ->options(Status::all()->pluck('name', 'id'))->default(1)
                ])
                ->action(function ($data) {
                    $status = $data['status'];
                    return Excel::download(new LeadsExport($status), 'Data Lead - '  . date('d-m-Y') . '.xlsx');
                }),
        ];
    }
}
