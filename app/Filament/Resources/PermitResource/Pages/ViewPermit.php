<?php

namespace App\Filament\Resources\PermitResource\Pages;

use App\Filament\Resources\PermitResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Support\Enums\FontWeight;

class ViewPermit extends ViewRecord
{
    protected static string $resource = PermitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->color('warning'),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Permit Information')
                    ->schema([
                        TextEntry::make('employee.name')
                            ->label('Employee')
                            ->weight(FontWeight::Medium)
                            ->icon('heroicon-o-user'),
                        TextEntry::make('date')
                            ->label('Permit Date')
                            ->date('d F Y')
                            ->icon('heroicon-o-calendar-days'),
                        TextEntry::make('start_time')
                            ->label('Start Time')
                            ->icon('heroicon-o-clock'),
                        TextEntry::make('end_time')
                            ->label('End Time')
                            ->icon('heroicon-o-clock'),
                        TextEntry::make('reason')
                            ->label('Reason')
                            ->columnSpanFull()
                            ->icon('heroicon-o-document-text'),
                    ])
                    ->columns(2),

                Section::make('Status & Approval')
                    ->schema([
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'pending' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                default => 'gray',
                            })
                            ->icon(fn(string $state): string => match ($state) {
                                'pending' => 'heroicon-o-clock',
                                'approved' => 'heroicon-o-check-circle',
                                'rejected' => 'heroicon-o-x-circle',
                                default => 'heroicon-o-question-mark-circle',
                            })
                            ->formatStateUsing(fn(string $state): string => ucfirst($state)),
                        TextEntry::make('request_date')
                            ->label('Request Date')
                            ->date('d F Y')
                            ->icon('heroicon-o-calendar'),
                        TextEntry::make('approvedBy.name')
                            ->label('Approved By')
                            ->placeholder('Not assigned')
                            ->icon('heroicon-o-user-check'),
                        TextEntry::make('approved_at')
                            ->label('Approved At')
                            ->dateTime('d F Y H:i')
                            ->placeholder('Not approved yet')
                            ->icon('heroicon-o-check'),
                    ])
                    ->columns(2),
            ]);
    }
}
