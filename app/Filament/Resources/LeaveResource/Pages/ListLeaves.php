<?php

namespace App\Filament\Resources\LeaveResource\Pages;

use App\Filament\Resources\LeaveResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\Select;
use App\Exports\LeavesExport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListLeaves extends ListRecords
{
    protected static string $resource = LeaveResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Requests')
                ->badge(LeaveResource::getModel()::count())
                ->badgeColor('gray'),

            'pending' => Tab::make('Pending')
                ->icon('heroicon-m-clock')
                ->badge(LeaveResource::getModel()::where('status', 'pending')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'pending')),

            'approved' => Tab::make('Approved')
                ->icon('heroicon-m-check-circle')
                ->badge(LeaveResource::getModel()::where('status', 'approved')->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'approved')),

            'rejected' => Tab::make('Rejected')
                ->icon('heroicon-m-x-circle')
                ->badge(LeaveResource::getModel()::where('status', 'rejected')->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'rejected')),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New Leave Request'),

            Actions\Action::make('excel')
                ->label('Export Excel')
                ->icon('heroicon-m-arrow-down-tray')
                ->color('gray')
                ->form([
                    Select::make('status')
                        ->label('Filter by Status')
                        ->options([
                            'all' => 'All Requests',
                            'pending' => 'Pending',
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                        ])
                        ->default('all')
                        ->required()
                ])
                ->action(function ($data) {
                    $status = $data['status'];
                    $filename = $status === 'all' ? 'All Leave Requests' : ucfirst($status) . ' Leave Requests';
                    return Excel::download(new LeavesExport($status === 'all' ? null : $status), $filename . ' - ' . date('d-m-Y') . '.xlsx');
                }),
        ];
    }
}
