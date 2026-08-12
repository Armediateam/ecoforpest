<?php

namespace App\Filament\Resources\LeaveResource\Pages;

use App\Filament\Resources\LeaveResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewLeave extends ViewRecord
{
    protected static string $resource = LeaveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->icon('heroicon-m-pencil'),
            Actions\DeleteAction::make()
                ->icon('heroicon-m-trash'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Leave Request Information')
                    ->icon('heroicon-m-calendar-days')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('employee.name')
                                    ->label('Employee')
                                    ->icon('heroicon-m-user'),

                                Infolists\Components\TextEntry::make('leave_type')
                                    ->label('Leave Type')
                                    ->icon('heroicon-m-calendar')
                                    ->badge()
                                    ->color(fn(?string $state): string => match ($state) {
                                        'Annual Leave' => 'success',
                                        'Sick Leave' => 'warning',
                                        'Emergency Leave' => 'danger',
                                        'Maternity Leave' => 'info',
                                        'Paternity Leave' => 'purple',
                                        default => 'gray',
                                    }),

                                Infolists\Components\TextEntry::make('start_date')
                                    ->label('Start Date')
                                    ->icon('heroicon-m-play')
                                    ->date('d F Y'),

                                Infolists\Components\TextEntry::make('end_date')
                                    ->label('End Date')
                                    ->icon('heroicon-m-stop')
                                    ->date('d F Y'),

                                Infolists\Components\TextEntry::make('duration')
                                    ->label('Duration')
                                    ->icon('heroicon-m-clock')
                                    ->getStateUsing(function ($record) {
                                        if ($record->start_date && $record->end_date) {
                                            $start = \Carbon\Carbon::parse($record->start_date);
                                            $end = \Carbon\Carbon::parse($record->end_date);
                                            $days = $start->diffInDays($end) + 1;
                                            return $days . ($days > 1 ? ' days' : ' day');
                                        }
                                        return 'Not specified';
                                    })
                                    ->badge()
                                    ->color('info'),

                                Infolists\Components\TextEntry::make('request_date')
                                    ->label('Request Date')
                                    ->icon('heroicon-m-calendar')
                                    ->date('d F Y'),
                            ]),

                        Infolists\Components\TextEntry::make('reason')
                            ->label('Reason for Leave')
                            ->icon('heroicon-m-document-text')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Status & Approval')
                    ->icon('heroicon-m-check-circle')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn(?string $state): string => match (strtolower($state)) {
                                        'pending' => 'warning',
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        default => 'gray',
                                    })
                                    ->icon(fn(?string $state): string => match (strtolower($state)) {
                                        'pending' => 'heroicon-m-clock',
                                        'approved' => 'heroicon-m-check-circle',
                                        'rejected' => 'heroicon-m-x-circle',
                                        default => 'heroicon-m-question-mark-circle',
                                    })
                                    ->formatStateUsing(fn(?string $state): string => ucfirst($state)),

                                Infolists\Components\TextEntry::make('approvedBy.name')
                                    ->label('Approved By')
                                    ->icon('heroicon-o-check-badge')
                                    ->placeholder('Not approved yet'),

                                Infolists\Components\TextEntry::make('approved_at')
                                    ->label('Approved At')
                                    ->icon('heroicon-o-check-badge')
                                    ->dateTime('d F Y, H:i')
                                    ->placeholder('Not approved yet'),
                            ]),
                    ]),

                Infolists\Components\Section::make('System Information')
                    ->icon('heroicon-m-information-circle')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Created At')
                                    ->dateTime('d F Y, H:i'),

                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->dateTime('d F Y, H:i'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
