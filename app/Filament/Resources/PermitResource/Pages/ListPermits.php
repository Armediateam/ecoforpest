<?php

namespace App\Filament\Resources\PermitResource\Pages;

use App\Filament\Resources\PermitResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\Select;
use App\Exports\PermitsExport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPermits extends ListRecords
{
    protected static string $resource = PermitResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Permits')
                ->icon('heroicon-o-document-text')
                ->badge(fn() => $this->getResource()::getEloquentQuery()->count()),
            'pending' => Tab::make('Pending')
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'pending'))
                ->badge(fn() => $this->getResource()::getEloquentQuery()->where('status', 'pending')->count())
                ->badgeColor('warning'),
            'approved' => Tab::make('Approved')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'approved'))
                ->badge(fn() => $this->getResource()::getEloquentQuery()->where('status', 'approved')->count())
                ->badgeColor('success'),
            'rejected' => Tab::make('Rejected')
                ->icon('heroicon-o-x-circle')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'rejected'))
                ->badge(fn() => $this->getResource()::getEloquentQuery()->where('status', 'rejected')->count())
                ->badgeColor('danger'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Permit')
                ->icon('heroicon-o-plus')
                ->color('primary'),
            Actions\Action::make('excel')
                ->label('Export to Excel')
                ->icon('heroicon-m-arrow-down-tray')
                ->color('success')
                ->form([
                    Select::make('status')
                        ->label('Filter by Status')
                        ->options([
                            'all' => 'All Status',
                            'pending' => 'Pending',
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                        ])
                        ->default('all')
                        ->native(false)
                        ->helperText('Select status to filter export data')
                ])
                ->action(function ($data) {
                    $status = $data['status'] === 'all' ? null : $data['status'];
                    $fileName = 'Permits_Export_' . ($status ? ucfirst($status) . '_' : '') . date('d-m-Y_H-i') . '.xlsx';
                    return Excel::download(new PermitsExport($status), $fileName);
                })
                ->modalHeading('Export Permits')
                ->modalDescription('Choose the status filter for exporting permits data.')
                ->modalSubmitActionLabel('Download Excel'),
        ];
    }
}
