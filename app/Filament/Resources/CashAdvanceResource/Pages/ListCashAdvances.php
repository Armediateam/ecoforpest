<?php

namespace App\Filament\Resources\CashAdvanceResource\Pages;

use App\Filament\Resources\CashAdvanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCashAdvances extends ListRecords
{
    protected static string $resource = CashAdvanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('export_cash_advance')
                ->label('Export Cash Advance')
                ->icon('heroicon-o-arrow-down-tray')
                ->form([
                    \Filament\Forms\Components\DatePicker::make('from')->label('From Date'),
                    \Filament\Forms\Components\DatePicker::make('until')->label('Until Date'),
                ])
                ->action(function (array $data) {
                    $query = \App\Models\CashAdvance::query();
                    if (!empty($data['from'])) {
                        $query->whereDate('date', '>=', $data['from']);
                    }
                    if (!empty($data['until'])) {
                        $query->whereDate('date', '<=', $data['until']);
                    }
                    $cashAdvances = $query->orderBy('date')->get();
                    $export = new \App\Exports\CashAdvanceReportExport($cashAdvances, $data);
                    $filename = 'cash-advance-report-' . now()->format('Ymd_His') . '.xlsx';
                    return \Maatwebsite\Excel\Facades\Excel::download($export, $filename);
                })
                ->color('success')
                ->modalDescription('Export laporan cash advance, filter by date.'),
        ];
    }
}
