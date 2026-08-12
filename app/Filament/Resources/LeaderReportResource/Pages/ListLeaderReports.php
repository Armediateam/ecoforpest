<?php

namespace App\Filament\Resources\LeaderReportResource\Pages;

use App\Filament\Resources\LeaderReportResource;
use App\Models\LeaderReport;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Exports\LeaderReportsExport;
use Maatwebsite\Excel\Facades\Excel;

class ListLeaderReports extends ListRecords
{
    protected static string $resource = LeaderReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Buat Laporan Baru')
                ->icon('heroicon-o-plus-circle'),
            Actions\Action::make('excel')
                ->label('Export')
                ->icon('heroicon-m-arrow-down-tray')
                ->color('gray')
                ->action(function () {
                    return Excel::download(new LeaderReportsExport(), 'Data Leader Reports - '  . date('d-m-Y') . '.xlsx');
                }),
        ];
    }
}
