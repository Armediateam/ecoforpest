<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Livewire\Components\Support\ResourceTabsConfiguration;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;

class ViewTask extends ViewRecord
{
    protected static string $resource = TaskResource::class;

    protected static string $view = 'filament.resources.task-resource.pages.view-task-tabs';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function getTabsConfiguration(): array
    {
        return ResourceTabsConfiguration::forServiceReport()->toArray();
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Task Details')
                    ->schema([
                        TextEntry::make('title')
                            ->columnSpanFull(),
                        TextEntry::make('description')
                            ->columnSpanFull(),
                        TextEntry::make('start_date')
                            ->dateTime('d M Y H:i'), // Format tanggal agar lebih mudah dibaca
                        TextEntry::make('end_date')
                            ->dateTime('d M Y H:i'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge() // Gunakan badge untuk tampilan lebih menarik
                            ->color(fn(string $state): string => match ($state) {
                                'To Do' => 'gray',
                                'In Progress' => 'info',
                                'Done' => 'success',
                                'Cancelled' => 'danger',
                            }),
                        TextEntry::make('prioritas')
                            ->label('Prioritas')
                            ->badge() // Gunakan badge untuk tampilan lebih menarik
                            ->color(fn(string $state): string => match ($state) {
                                'Rendah' => 'gray',
                                'Sedang' => 'info',
                                'Tinggi' => 'warning',
                                'Sangat Tinggi' => 'danger',
                            }),
                    ])->columns(2),

                // Menampilkan data dari Repeater
                RepeatableEntry::make('taskRecurrence')
                    ->label('Task Recurrence')
                    ->schema([
                        TextEntry::make('frequency'),
                        TextEntry::make('interval'),
                        TextEntry::make('days_of_week'),
                        TextEntry::make('days_of_month'),
                        TextEntry::make('total_cycle')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Related To')
                    ->schema([
                        TextEntry::make('task_type')
                            ->label('Tipe Task'),
                        // Gunakan dot notation untuk menampilkan data dari relasi
                        TextEntry::make('proposal.subject')
                            ->label('Proposal')
                            // Logika visibility diubah untuk menggunakan $record
                            ->visible(fn($record) => $record->task_type === 'proposal'),
                        TextEntry::make('customer.name')
                            ->label('Customer')
                            ->visible(fn($record) => $record->task_type === 'customer'),
                        TextEntry::make('user.name')
                            ->label('User')
                            ->visible(fn($record) => $record->task_type === 'user'),
                        TextEntry::make('lead.name')
                            ->label('Lead')
                            ->visible(fn($record) => $record->task_type === 'lead'),
                        TextEntry::make('catatan')
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Viewers')
                    ->schema([
                        // Untuk relasi many-to-many, ini akan menampilkan semua nama dengan badge
                        TextEntry::make('viewers.name')
                            ->label('Viewers')
                            ->badge(),
                    ])->columnSpanFull(),
            ]);
    }
}
