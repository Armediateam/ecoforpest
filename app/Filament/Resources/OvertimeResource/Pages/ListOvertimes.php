<?php

namespace App\Filament\Resources\OvertimeResource\Pages;

use App\Filament\Resources\OvertimeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\Select;
use App\Exports\OvertimesExport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOvertimes extends ListRecords
{
    protected static string $resource = OvertimeResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Overtime')
                ->icon('heroicon-o-clipboard-document-list')
                ->badge(fn() => $this->getModel()::count()),

            'pending' => Tab::make('Pending')
                ->icon('heroicon-o-clock')
                ->badge(fn() => $this->getModel()::where('status', 'pending')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'pending')),

            'approved' => Tab::make('Approved')
                ->icon('heroicon-o-check-circle')
                ->badge(fn() => $this->getModel()::where('status', 'approved')->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'approved')),

            'rejected' => Tab::make('Rejected')
                ->icon('heroicon-o-x-circle')
                ->badge(fn() => $this->getModel()::where('status', 'rejected')->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'rejected')),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus')
                ->label('New Overtime Request'),
            Actions\Action::make('excel')
                ->label('Export Data')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->form([
                    Select::make('status')
                        ->label('Filter by Status')
                        ->options([
                            'all' => 'All Status',
                            'pending' => 'Pending',
                            'rejected' => 'Rejected',
                            'approved' => 'Approved',
                        ])
                        ->default('all')
                        ->helperText('Select status to export specific records')
                ])
                ->action(function ($data) {
                    $status = $data['status'] === 'all' ? null : $data['status'];
                    return Excel::download(new OvertimesExport($status), 'Overtime_Data_' . date('d-m-Y') . '.xlsx');
                }),
        ];
    }

    public function getTitle(): string
    {
        return 'Overtime Management';
    }

    public function getHeading(): string
    {
        return 'Overtime Requests';
    }

    public function getSubheading(): ?string
    {
        return 'Manage and track employee overtime requests with approval workflow';
    }
}
