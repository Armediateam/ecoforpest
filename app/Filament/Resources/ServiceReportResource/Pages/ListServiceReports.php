<?php

namespace App\Filament\Resources\ServiceReportResource\Pages;

use App\Filament\Resources\ServiceReportResource;
use App\Models\ServiceReport;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListServiceReports extends ListRecords
{
    protected static string $resource = ServiceReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Service Report')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Reports')
                ->badge(ServiceReport::count()),

            'pending_approval' => Tab::make('Pending Approval')
                ->modifyQueryUsing(fn(Builder $query) => $query->where(function ($q) {
                    $q->where('technician_approve', false)
                        ->orWhere('client_approve', false);
                }))
                ->badge(ServiceReport::where(function ($q) {
                    $q->where('technician_approve', false)
                        ->orWhere('client_approve', false);
                })->count())
                ->badgeColor('warning'),

            'approved' => Tab::make('Fully Approved')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('technician_approve', true)
                    ->where('client_approve', true))
                ->badge(ServiceReport::where('technician_approve', true)
                    ->where('client_approve', true)->count())
                ->badgeColor('success'),

            // 'email_pending' => Tab::make('Email Pending')
            //     ->modifyQueryUsing(fn(Builder $query) => $query->where('email_sent', false))
            //     ->badge(ServiceReport::where('email_sent', false)->count())
            //     ->badgeColor('info'),

            'incomplete' => Tab::make('Incomplete')
                ->modifyQueryUsing(fn(Builder $query) => $query->where(function ($q) {
                    $q->whereNull('close_order')
                        ->orWhereNull('technician_signature')
                        ->orWhereNull('client_signature');
                }))
                ->badge(ServiceReport::where(function ($q) {
                    $q->whereNull('close_order')
                        ->orWhereNull('technician_signature')
                        ->orWhereNull('client_signature');
                })->count())
                ->badgeColor('danger'),
        ];
    }
}
