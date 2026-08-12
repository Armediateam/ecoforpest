<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Exports\UsersExport;
use Maatwebsite\Excel\Facades\Excel;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('excel')
                ->label('Export')
                ->icon('heroicon-m-arrow-down-tray')
                ->color('gray')
                ->action(function () {
                    return Excel::download(new UsersExport(), 'Data Staff User - '  . date('d-m-Y') . '.xlsx');
                }),
        ];
    }
}
