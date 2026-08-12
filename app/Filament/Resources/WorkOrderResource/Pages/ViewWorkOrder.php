<?php

namespace App\Filament\Resources\WorkOrderResource\Pages;

use App\Filament\Resources\WorkOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Afsakar\LeafletMapPicker\LeafletMapPickerEntry;
use App\Livewire\Components\Support\ResourceTabsConfiguration;
use App\Models\Action;
use Illuminate\Support\Facades\Auth;
use App\Models\WorkOrderSurvey;
use App\Models\SurveyForm;
use App\Models\WorkOrderProgress;
use App\Models\WorkOrder;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ViewWorkOrder extends ViewRecord
{
    protected static string $resource = WorkOrderResource::class;

    protected static string $view = 'filament.resources.work-order-resource.pages.view-wo-tabs';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view_service_report')
                ->color('info')
                ->label('View Service Report')
                ->icon('heroicon-o-document-text')
                ->url(fn() => $this->record->serviceReport ? route('filament.secret.work-orders.resources.service-reports.view', $this->record->serviceReport) : '#')
                ->openUrlInNewTab()
                ->visible(fn() => $this->record->serviceReport),
            Actions\EditAction::make()
                ->icon('heroicon-o-pencil'),
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash'),
            Actions\Action::make('rollback_progress')
                ->icon('heroicon-c-arrow-path-rounded-square')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Rollback Progress')
                ->action(function () {
                    $workOrder = $this->record;
                    $user = Auth::user();
                    $latest = WorkOrderProgress::where('work_order_id', $workOrder->id)->latest('completed_at')->first();
                    if (! $latest) {
                        $this->notify('warning', 'No progress entries to rollback.');
                        Notification::make()
                            ->title('No progress entries to rollback.')
                            ->warning()
                            ->send();
                        return;
                    }
                    DB::beginTransaction();
                    try {
                        if($latest->progress_status == "Survey"){
                            $surveyFormIdentificationId = SurveyForm::where('service_id', $workOrder->service_id)
                                ->where('type', 'identification')
                                ->latest()
                                ->first();
                            if(! $surveyFormIdentificationId){
                                throw new \Exception('Survey Form Identification not found for rollback.');
                            }
                            $woSurveyFormIdentification = WorkOrderSurvey::where('work_order_id', $workOrder->id)
                                ->where('survey_form_id', $surveyFormIdentificationId->id)
                                ->first();
                            if ($woSurveyFormIdentification) {
                                $woSurveyFormIdentification->delete();
                            }
                            $surveyFormInitialId = SurveyForm::where('service_id', $workOrder->service_id)
                                ->where('type', 'initial_check')
                                ->latest()
                                ->first();
                            if(! $surveyFormInitialId){
                                throw new \Exception('Survey Form Initial not found for rollback.');
                            }
                            $woSurveyFormInitial = WorkOrderSurvey::where('work_order_id', $workOrder->id)
                                ->where('survey_form_id', $surveyFormInitialId->id)
                                ->first();
                            if ($woSurveyFormIdentification) {
                                $woSurveyFormIdentification->delete();
                            }
                        } else if ($latest->progress_status == "Selesai Kerja"){
                            $surveyFormFinal = SurveyForm::where('service_id', $workOrder->service_id)
                                ->where('type', 'final_check')
                                ->latest()
                                ->first();
                            if(! $surveyFormFinal){
                                throw new \Exception('Survey Form Initial not found for rollback.');
                            }
                            $woSurveyFormInitial = WorkOrderSurvey::where('work_order_id', $workOrder->id)
                                ->where('survey_form_id', $surveyFormFinal->id)
                                ->first();
                            if ($woSurveyFormInitial) {
                                $woSurveyFormInitial->delete();
                            }
                        }

                        $latest->delete();
                        $remaining = $workOrder->progress()->orderBy('completed_at', 'desc')->first();
                        if (! $remaining) {
                            $workOrder->status = WorkOrder::STATUS_ASSIGNED;
                        } else {
                            $workOrder->status = WorkOrder::STATUS_ON_PROGRESS;
                        }
                        $workOrder->save();

                        if (function_exists('activity')) {
                            activity()
                                ->performedOn($workOrder)
                                ->causedBy($user)
                                ->withProperties(['rolled_back_progress_id' => $latest->id, 'rolled_back_progress_status' => $latest->progress_status])
                                ->log('progress_rolled_back');
                        }

                        DB::commit();

                        Notification::make()
                            ->title('Latest progress rolled back successfully.')
                            ->success()
                            ->send();
                        $this->redirect($this->getResource()::getUrl('view', ['record' => $workOrder->id]));
                    } catch (\Exception $e) {
                        DB::rollBack();
                        Log::error('Rollback progress error: ' . $e->getMessage());
                        Notification::make()
                            ->title('Failed to rollback progress.')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function getTabsConfiguration(): array
    {
        return ResourceTabsConfiguration::forWO()->toArray();
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Grid::make(2)
                    ->schema([
                        Components\Group::make([
                            // Service & Package Information Section
                            Components\Section::make('Service & Package Information')
                                ->icon('heroicon-m-wrench-screwdriver')
                                ->schema([
                                    Components\TextEntry::make('service.name')
                                        ->label('Service Type'),
                                    Components\RepeatableEntry::make('workOrderPackage')
                                        ->schema([
                                            Components\TextEntry::make('package.name')
                                                ->label('Package Name'),
                                        ])
                                        ->label('Service Packages')
                                        ->columns(3),
                                    Components\TextEntry::make('total')
                                        ->label('Total Amount')
                                        ->money('IDR')
                                        ->columnSpanFull(),
                                    Components\TextEntry::make('guarantee')
                                        ->label('Guarantee Period')
                                        ->columnSpanFull(),
                                ])->columns(1)->collapsible(),

                            // Client Information Section
                            Components\Section::make('Client Information')
                                ->icon('heroicon-m-user-group')
                                ->schema([
                                    Components\TextEntry::make('related')
                                        ->label('Client Type')
                                        ->formatStateUsing(fn(string $state): string => ucfirst($state)),

                                    Components\TextEntry::make('customer.name')
                                        ->label('Customer Name')
                                        ->visible(fn($record) => $record->related === 'customer'),

                                    Components\TextEntry::make('customer.phone')
                                        ->label('Phone Number')
                                        ->visible(fn($record) => $record->related === 'customer')
                                        ->formatStateUsing(fn(?string $state): string => $state ?: 'n/a'),

                                    Components\TextEntry::make('customer.email')
                                        ->label('Email')
                                        ->visible(fn($record) => $record->related === 'customer')
                                        ->formatStateUsing(fn(?string $state): string => $state ?: 'n/a'),

                                    Components\TextEntry::make('customer.company')
                                        ->label('Company')
                                        ->visible(fn($record) => $record->related === 'customer')
                                        ->formatStateUsing(fn(?string $state): string => $state ?: 'n/a'),

                                    Components\TextEntry::make('lead.name')
                                        ->label('Lead Name')
                                        ->visible(fn($record) => $record->related === 'lead'),

                                    Components\TextEntry::make('lead.phone')
                                        ->label('Phone Number')
                                        ->visible(fn($record) => $record->related === 'lead')
                                        ->formatStateUsing(fn(?string $state): string => $state ?: 'n/a'),

                                    Components\TextEntry::make('lead.email')
                                        ->label('Email')
                                        ->visible(fn($record) => $record->related === 'lead')
                                        ->formatStateUsing(fn(?string $state): string => $state ?: 'n/a'),

                                    Components\TextEntry::make('lead.company')
                                        ->label('Company')
                                        ->visible(fn($record) => $record->related === 'lead')
                                        ->formatStateUsing(fn(?string $state): string => $state ?: 'n/a'),
                                ])->columns(1)->collapsible(),
                            // Assignment & Status Section
                            Components\Section::make('Assignment & Status')
                                ->icon('heroicon-m-user-plus')
                                ->schema([
                                    //assign worker
                                    Components\TextEntry::make('assigned.name')
                                        ->label('Assigned Worker')
                                        ->badge(),
                                    //assign helper
                                    Components\TextEntry::make('helpers.name')
                                        ->badge()
                                        ->label('Assigned Helper'),
                                    Components\TextEntry::make('status')
                                        ->label('Status')
                                        ->badge() // Tampilkan status sebagai badge
                                        ->color(fn(string $state): string => match ($state) {
                                            'Pending' => 'gray',
                                            'Assigned' => 'info',
                                            'In Progress' => 'warning',
                                            'Completed' => 'success',
                                            'Cancelled' => 'danger',
                                            default => 'gray',
                                        }),
                                ])->columns(2)->collapsible(),
                        ]),

                        // KOLOM KANAN
                        Components\Group::make([
                            // Location & Address Section
                            Components\Section::make('Location & Address Information')
                                ->icon('heroicon-m-map-pin')
                                ->schema([
                                    Components\TextEntry::make('alamat')
                                        ->label('Full Address')
                                        ->columnSpanFull(),

                                    LeafletMapPickerEntry::make('position')
                                        ->label('GPS Coordinates')
                                        ->tileProvider('google')
                                        ->hideTileControl()
                                        ->state(function ($record) {
                                            if (empty($record->position) || !isset($record->position[0]['latitude'])) {
                                                return null;
                                            }
                                            return [
                                                'lat' => (float) $record->position[0]['latitude'],
                                                'lng' => (float) $record->position[0]['longitude'],
                                            ];
                                        })
                                        ->visible(fn($record) => !empty($record->position) && isset($record->position[0])),
                                    // Components\TextEntry::make('leaflet_map_picker')
                                    //     ->label('Coordinate')
                                    //     ->columnSpanFull(),

                                    // LeafletMapPickerEntry::make('leaflet_map_picker')
                                    //     ->label('Property Location')
                                    //     ->height('500px')
                                    //     ->tileProvider('google')
                                    //     ->hideTileControl(),

                                    Components\TextEntry::make('address_note')
                                        ->label('Additional Address Notes')
                                        ->columnSpanFull(),
                                ])->columns(1)->collapsible(),

                            // Work Schedule Section
                            Components\Section::make('Work Schedule')
                                ->icon('heroicon-m-calendar-days')
                                ->schema([
                                    Components\TextEntry::make('work_date')
                                        ->label('Work Date')
                                        ->date('d F Y'), // Format tanggal
                                    Components\TextEntry::make('work_time')
                                        ->label('Work Time')
                                        ->time('H:i'), // Format waktu

                                    Components\IconEntry::make('is_recuring')
                                        ->label('Recurring Work Order')
                                        ->boolean() // Tampilkan ikon check/x
                                        ->columnSpanFull(),

                                    Components\Grid::make(3)
                                        ->visible(fn($record): bool => $record->is_recuring)
                                        ->schema([
                                            Components\TextEntry::make('repeat_every')
                                                ->label('Repeat Every'),
                                            Components\TextEntry::make('repeat_type')
                                                ->label('Repeat Type')
                                                ->formatStateUsing(fn(string $state): string => ucfirst($state)),
                                            Components\TextEntry::make('repeat_cycle')
                                                ->label('Number of Cycles'),
                                        ]),
                                ])->columns(2)->collapsible(),


                        ]),
                    ]),

                // Work Details Section
                Components\Section::make('Scope of Works')
                    ->icon('heroicon-m-document-text')
                    ->schema([
                        Components\TextEntry::make('target_pest')
                            ->label('Target Pest')
                            ->columnSpanFull(),

                        Components\RepeatableEntry::make('tindakan')
                            ->label('Tindakan')
                            ->schema([
                                Components\TextEntry::make('name')->label('Nama'),
                                Components\TextEntry::make('description')->label('Deskripsi'),
                            ])
                            ->columnSpanFull(),
                        Components\TextEntry::make('detail_work')
                            ->html()
                            ->label('Work Description')
                            ->columnSpanFull(),
                    ])->columns(1)->collapsible(),
                // Progress History Section - Selalu terlihat di mode View
                Components\Section::make('Progress History')
                    ->icon('heroicon-m-clock')
                    ->schema([
                        Components\RepeatableEntry::make('progress')
                            ->schema([
                                Components\TextEntry::make('progress_status')
                                    ->label('Status'),
                                Components\TextEntry::make('notes')
                                    ->label('Notes'),
                                Components\TextEntry::make('completed_at')
                                    ->label('Completed At')
                                    ->dateTime('d F Y, H:i'),
                                Components\TextEntry::make('completedBy.name') // Relasi ke user yg menyelesaikan
                                    ->label('Completed By'),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }
}
