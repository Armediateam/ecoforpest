<?php

namespace App\Filament\Resources\WorkOrderResource\Pages;

use App\Filament\Resources\WorkOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Exports\WorkOrdersExport;
use Maatwebsite\Excel\Facades\Excel;

class ListWorkOrders extends ListRecords
{
    protected static string $resource = WorkOrderResource::class;

    protected static string $view = 'filament.resources.work-order-resource.pages.list-work-orders';

    public ?string $activeTab = 'calendar';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('excel')
                ->label('Export')
                ->icon('heroicon-m-arrow-down-tray')
                ->color('gray')
                ->action(function () {
                    return Excel::download(new WorkOrdersExport(), 'Data Work Order - '  . date('d-m-Y') . '.xlsx');
                }),
        ];
    }
}
