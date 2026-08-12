<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceReportResource\Pages;
use App\Filament\Resources\ServiceReportResource\RelationManagers;
use App\Models\ServiceReport;
use App\Models\WorkOrder;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Support\Enums\FontWeight;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use App\Filament\Clusters\WorkOrders;

class ServiceReportResource extends Resource
{
    protected static ?string $model = ServiceReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Service Reports';

    protected static ?string $cluster = WorkOrders::class;

    protected static ?string $modelLabel = 'Service Report';

    protected static ?string $pluralModelLabel = 'Service Reports';

    protected static ?int $navigationSort = 15;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        // LEFT COLUMN
                        Forms\Components\Group::make([
                            // Work Order Information Section
                            Forms\Components\Section::make('Work Order Information')
                                ->description('Select work order and auto-populate service details')
                                ->icon('heroicon-m-clipboard-document-list')
                                ->schema([
                                    Forms\Components\Select::make('work_order_id')
                                        ->label('Work Order')
                                        ->placeholder('Select a work order...')
                                        ->relationship('workOrder', 'id', function ($query) {
                                            return $query->with(['customer', 'assigned']);
                                        })
                                        ->getOptionLabelFromRecordUsing(fn($record) => "#{$record->id} - " . ($record->customer?->name ?? 'No Customer'))
                                        ->searchable(['id', 'customer.name'])
                                        ->preload()
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function ($state, Set $set) {
                                            if (!$state) return;

                                            $workOrder = WorkOrder::with(['customer', 'assigned'])->find($state);
                                            if ($workOrder) {
                                                $set('customer_name', $workOrder->customer?->name ?? '');
                                                $set('work_order_number', "WO-{$workOrder->id}");
                                                $set('technician_name', $workOrder->assigned?->name ?? '');
                                            }
                                        })
                                        ->columnSpanFull(),

                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('work_order_number')
                                                ->label('Work Order Number')
                                                ->readOnly()
                                                ->placeholder('Auto-filled from work order'),

                                            Forms\Components\TextInput::make('customer_name')
                                                ->label('Customer Name')
                                                ->readOnly()
                                                ->placeholder('Auto-filled from work order'),
                                        ]),
                                ])->collapsible(),

                            // Service Completion Section
                            Forms\Components\Section::make('Service Completion')
                                ->description('Service completion details and timing')
                                ->icon('heroicon-m-check-circle')
                                ->schema([
                                    Forms\Components\DateTimePicker::make('close_order')
                                        ->label('Service Completion Date & Time')
                                        ->helperText('When was the service completed?')
                                        ->native(false)
                                        ->displayFormat('d/m/Y H:i')
                                        ->placeholder('Select completion date and time')
                                        ->before('now')
                                        ->required(fn(Get $get) => $get('technician_approve') || $get('client_approve'))
                                        ->columnSpanFull(),
                                ])->collapsible(),
                        ]),

                        // RIGHT COLUMN
                        Forms\Components\Group::make([
                            // Technician Information Section
                            Forms\Components\Section::make('Technician Information')
                                ->description('Technician assignment and signature details')
                                ->icon('heroicon-m-user-circle')
                                ->schema([
                                    Forms\Components\TextInput::make('technician_name')
                                        ->label('Assigned Technician')
                                        ->readOnly()
                                        ->placeholder('Auto-filled from work order')
                                        ->columnSpanFull(),

                                    Forms\Components\FileUpload::make('technician_signature')
                                        ->label('Technician Signature')
                                        ->directory('signatures/technician')
                                        ->image()
                                        ->imageEditor()
                                        ->acceptedFileTypes(['image/png', 'image/jpeg'])
                                        ->maxSize(2048)
                                        ->helperText('Upload technician signature image')
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make('technician_signature_name')
                                        ->label('Technician Name for Signature')
                                        ->maxLength(255)
                                        ->placeholder('Full name as shown in signature')
                                        ->columnSpanFull(),
                                ])->collapsible(),

                            // Client Information Section
                            Forms\Components\Section::make('Client Information')
                                ->description('Client approval and signature details')
                                ->icon('heroicon-m-identification')
                                ->schema([
                                    Forms\Components\FileUpload::make('client_signature')
                                        ->label('Client Signature')
                                        ->directory('signatures/client')
                                        ->image()
                                        ->imageEditor()
                                        ->acceptedFileTypes(['image/png', 'image/jpeg'])
                                        ->maxSize(2048)
                                        ->helperText('Upload client signature image')
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make('client_signature_name')
                                        ->label('Client Name for Signature')
                                        ->maxLength(255)
                                        ->placeholder('Full name as shown in signature')
                                        ->columnSpanFull(),
                                ])->collapsible(),
                        ]),
                    ]),

                // Approval & Status Section (Full Width)
                Forms\Components\Section::make('Approval & Status')
                    ->description('Service approval status and communication')
                    ->icon('heroicon-m-shield-check')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Toggle::make('technician_approve')
                                    ->label('Technician Approval')
                                    ->helperText('Technician confirms service completion')
                                    ->live()
                                    ->inline(false),

                                Forms\Components\Toggle::make('client_approve')
                                    ->label('Client Approval')
                                    ->helperText('Client confirms satisfaction with service')
                                    ->live()
                                    ->inline(false),

                                // Forms\Components\Toggle::make('email_sent')
                                //     ->label('Email Notification Sent')
                                //     ->helperText('Email report sent to client')
                                //     ->inline(false),
                            ]),

                        Forms\Components\TextInput::make('signature_url')
                            ->label('Digital Signature URL')
                            ->url()
                            ->placeholder('Optional: Digital signature service URL')
                            ->columnSpanFull(),
                    ])->collapsible(),

                // Hidden audit fields
                Forms\Components\Hidden::make('created_by')
                    ->dehydrateStateUsing(fn() => auth()->id()),

                Forms\Components\Hidden::make('updated_by')
                    ->dehydrateStateUsing(fn() => auth()->id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client')
                    ->label('Customer / Lead')
                    ->getStateUsing(function ($record) {
                        if (!$record->workOrder) {
                            return '-';
                        }
                        if ($record->workOrder->customer) {
                            return 'Customer : ' . $record->workOrder->customer?->name;
                        } elseif ($record->workOrder->lead) {
                            return 'Lead : ' . $record->workOrder->lead?->name;
                        }
                        return '-';
                    })
                    ->limit(30)
                    ->placeholder('No Customer / Lead')
                    ->badge()
                    ->icon(fn($record) => $record->workOrder && $record->workOrder->customer ? 'heroicon-o-building-office' : 'heroicon-o-user-plus')
                    ->color(fn($record) => $record->workOrder && $record->workOrder->customer ? 'success' : 'warning')
                    ->sortable()
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (!$state || strlen($state) <= 30) {
                            return null;
                        }
                        return $state;
                    }),
                // Tables\Columns\TextColumn::make('workOrder.customer.name')
                //     ->label('Customer / Lead')
                //     ->searchable()
                //     ->sortable()
                //     ->limit(30)
                //     ->placeholder('No Customer / Lead')
                //     ->badge()
                //     ->color(fn($record) => $record->workOrder?->customer?->lead_id ? 'success' : 'warning')
                //     ->icon(fn($record) => $record->workOrder?->customer?->lead_id ? 'heroicon-o-building-office' : 'heroicon-o-user-plus')
                //     ->formatStateUsing(function ($state, $record) {
                //         if (!$record->workOrder?->customer) {
                //             return 'No Customer';
                //         }
                //         $prefix = $record->workOrder->customer->lead_id ? 'Customer' : 'Lead';
                //         return $prefix . ': ' . ($state ?: 'Unnamed');
                //     })
                //     ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                //         $state = $column->getState();
                //         if (!$state || strlen($state) <= 30) {
                //             return null;
                //         }
                //         return $state;
                //     }),

                Tables\Columns\TextColumn::make('workOrder.service.name')
                    ->label('Service Type')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('No Service'),

                Tables\Columns\TextColumn::make('technician_name')
                    ->label('Technician')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-user-circle'),

                Tables\Columns\TextColumn::make('close_order')
                    ->label('Completed At')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->placeholder('Not completed')
                    ->color('success')
                    ->icon('heroicon-m-clock'),

                Tables\Columns\ViewColumn::make('approval_status')
                    ->label('Approval Status')
                    ->view('filament.tables.columns.service-report-approval')
                    ->alignCenter(),

                Tables\Columns\ViewColumn::make('signatures')
                    ->label('Signatures')
                    ->view('filament.tables.columns.service-report-signatures')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->toggleable()
                    ->placeholder('System'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('workOrder.service_id')
                    ->label('Service Type')
                    ->relationship('workOrder.service', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('technician_name')
                    ->label('Technician')
                    ->options(function () {
                        return ServiceReport::query()
                            ->distinct()
                            ->whereNotNull('technician_name')
                            ->pluck('technician_name', 'technician_name')
                            ->toArray();
                    })
                    ->searchable(),

                Tables\Filters\Filter::make('approval_status')
                    ->label('Approval Status')
                    ->form([
                        Forms\Components\Select::make('approval')
                            ->label('Approval Status')
                            ->options([
                                'pending' => 'Pending Approval',
                                'technician_only' => 'Technician Approved Only',
                                'client_only' => 'Client Approved Only',
                                'both_approved' => 'Both Approved',
                            ])
                            ->placeholder('All statuses'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['approval'],
                            function (Builder $query, $approval): Builder {
                                return match ($approval) {
                                    'pending' => $query->where('technician_approve', false)
                                        ->where('client_approve', false),
                                    'technician_only' => $query->where('technician_approve', true)
                                        ->where('client_approve', false),
                                    'client_only' => $query->where('technician_approve', false)
                                        ->where('client_approve', true),
                                    'both_approved' => $query->where('technician_approve', true)
                                        ->where('client_approve', true),
                                    default => $query,
                                };
                            }
                        );
                    }),

                // Tables\Filters\TernaryFilter::make('email_sent')
                //     ->label('Email Status')
                //     ->placeholder('All emails')
                //     ->trueLabel('Email sent')
                //     ->falseLabel('Email not sent'),

                Tables\Filters\Filter::make('completion_date')
                    ->label('Completion Date')
                    ->form([
                        Forms\Components\DatePicker::make('completed_from')
                            ->label('Completed From'),
                        Forms\Components\DatePicker::make('completed_until')
                            ->label('Completed Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['completed_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('close_order', '>=', $date),
                            )
                            ->when(
                                $data['completed_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('close_order', '<=', $date),
                            );
                    }),

                Tables\Filters\Filter::make('has_signatures')
                    ->label('Signature Status')
                    ->form([
                        Forms\Components\Select::make('signature_status')
                            ->label('Signature Status')
                            ->options([
                                'no_signatures' => 'No Signatures',
                                'technician_only' => 'Technician Signature Only',
                                'client_only' => 'Client Signature Only',
                                'both_signatures' => 'Both Signatures',
                            ])
                            ->placeholder('All signature statuses'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['signature_status'],
                            function (Builder $query, $status): Builder {
                                return match ($status) {
                                    'no_signatures' => $query->whereNull('technician_signature')
                                        ->whereNull('client_signature'),
                                    'technician_only' => $query->whereNotNull('technician_signature')
                                        ->whereNull('client_signature'),
                                    'client_only' => $query->whereNull('technician_signature')
                                        ->whereNotNull('client_signature'),
                                    'both_signatures' => $query->whereNotNull('technician_signature')
                                        ->whereNotNull('client_signature'),
                                    default => $query,
                                };
                            }
                        );
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->color('info'),

                    Tables\Actions\EditAction::make()
                        ->color('warning'),

                    Tables\Actions\Action::make('download_report')
                        ->label('Download PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->url(
                            fn(ServiceReport $record): string =>
                            '#' // TODO: Implement PDF generation route
                        )
                        ->openUrlInNewTab(),

                    // Tables\Actions\Action::make('resend_email')
                    //     ->label('Resend Email')
                    //     ->icon('heroicon-o-envelope')
                    //     ->color('primary')
                    //     ->requiresConfirmation()
                    //     ->modalDescription('Are you sure you want to resend the service report email to the client?')
                    //     ->action(function (ServiceReport $record) {
                    //         // Add email sending logic here
                    //         $record->update(['email_sent' => true]);
                    //     })
                    //     ->successNotificationTitle('Email sent successfully')
                    //     ->visible(fn(ServiceReport $record): bool => !$record->email_sent),

                    Tables\Actions\DeleteAction::make()
                        ->requiresConfirmation(),
                ])
                    ->label('Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\BulkAction::make('mark_email_sent')
                    //     ->label('Mark Email Sent')
                    //     ->icon('heroicon-o-envelope')
                    //     ->color('success')
                    //     ->action(function ($records) {
                    //         $records->each(fn($record) => $record->update(['email_sent' => true]));
                    //     })
                    //     ->requiresConfirmation()
                    //     ->modalDescription('Mark selected reports as email sent?')
                    //     ->successNotificationTitle('Email status updated'),

                    Tables\Actions\BulkAction::make('approve_technician')
                        ->label('Approve Technician')
                        ->icon('heroicon-o-check-circle')
                        ->color('warning')
                        ->action(function ($records) {
                            $records->each(fn($record) => $record->update(['technician_approve' => true]));
                        })
                        ->requiresConfirmation()
                        ->modalDescription('Approve technician for selected reports?')
                        ->successNotificationTitle('Technician approval updated'),

                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchOnBlur()
            ->striped()
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistColumnSearchesInSession();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServiceReports::route('/'),
            'create' => Pages\CreateServiceReport::route('/create'),
            'view' => Pages\ViewServiceReport::route('/{record}'),
            'edit' => Pages\EditServiceReport::route('/{record}/edit'),
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Grid::make(2)
                    ->schema([
                        // LEFT COLUMN
                        Infolists\Components\Group::make([
                            Infolists\Components\Section::make('Work Order Information')
                                ->icon('heroicon-m-clipboard-document-list')
                                ->schema([
                                    Infolists\Components\TextEntry::make('work_order_number')
                                        ->label('Work Order Number')
                                        ->copyable()
                                        ->icon('heroicon-m-clipboard'),

                                    Infolists\Components\TextEntry::make('workOrder.customer.name')
                                        ->label('Customer / Lead')
                                        ->visible(fn($record) => $record->workOrder && $record->workOrder->related === 'customer'),

                                    Infolists\Components\TextEntry::make('workOrder.lead.name')
                                        ->label('Customer / Lead')
                                        ->visible(fn($record) => $record->workOrder && $record->workOrder->related === 'lead'),

                                    Infolists\Components\TextEntry::make('workOrder.service.name')
                                        ->label('Service Type')
                                        ->badge()
                                        ->icon('heroicon-m-wrench-screwdriver'),

                                    Infolists\Components\TextEntry::make('close_order')
                                        ->label('Service Completed')
                                        ->dateTime('d M Y, H:i')
                                        ->placeholder('Not completed')
                                        ->icon('heroicon-m-clock'),
                                ])
                                ->columns(2),

                            Infolists\Components\Section::make('Technician Information')
                                ->icon('heroicon-m-user-circle')
                                ->schema([
                                    Infolists\Components\TextEntry::make('technician_name')
                                        ->label('Assigned Technician')
                                        ->icon('heroicon-m-identification'),

                                    Infolists\Components\ImageEntry::make('technician_signature')
                                        ->label('Technician Signature')
                                        ->height(100)
                                        ->visibility('public')
                                        ->placeholder('No signature uploaded'),

                                    Infolists\Components\TextEntry::make('technician_signature_name')
                                        ->label('Signature Name')
                                        ->placeholder('No name provided'),
                                ])
                                ->columns(1),
                        ]),

                        // RIGHT COLUMN
                        Infolists\Components\Group::make([
                            Infolists\Components\Section::make('Client Information')
                                ->icon('heroicon-m-identification')
                                ->schema([
                                    Infolists\Components\ImageEntry::make('client_signature')
                                        ->label('Client Signature')
                                        ->height(100)
                                        ->visibility('public')
                                        ->placeholder('No signature uploaded'),

                                    Infolists\Components\TextEntry::make('client_signature_name')
                                        ->label('Client Name')
                                        ->placeholder('No name provided'),
                                ])
                                ->columns(1),

                            Infolists\Components\Section::make('Approval & Status')
                                ->icon('heroicon-m-shield-check')
                                ->schema([
                                    Infolists\Components\Grid::make(3)
                                        ->schema([
                                            Infolists\Components\IconEntry::make('technician_approve')
                                                ->label('Technician Approval')
                                                ->boolean()
                                                ->trueIcon('heroicon-o-check-circle')
                                                ->falseIcon('heroicon-o-x-circle')
                                                ->trueColor('success')
                                                ->falseColor('danger'),

                                            Infolists\Components\IconEntry::make('client_approve')
                                                ->label('Client Approval')
                                                ->boolean()
                                                ->trueIcon('heroicon-o-check-circle')
                                                ->falseIcon('heroicon-o-x-circle')
                                                ->trueColor('success')
                                                ->falseColor('danger'),

                                            // Infolists\Components\IconEntry::make('email_sent')
                                            //     ->label('Email Notification')
                                            //     ->boolean()
                                            //     ->trueIcon('heroicon-o-envelope')
                                            //     ->falseIcon('heroicon-o-envelope')
                                            //     ->trueColor('success')
                                            //     ->falseColor('gray'),
                                        ]),

                                    Infolists\Components\TextEntry::make('signature_token_url')
                                        ->label('Digital Signature URL')
                                        ->formatStateUsing(fn($state) => $state ? 'View Signature' : null)
                                        ->badge()
                                        ->icon('heroicon-m-link')
                                        ->url(fn($state) => $state)
                                        ->openUrlInNewTab()
                                        ->placeholder('No digital signature URL')
                                        ->columnSpanFull(),
                                ])
                                ->columns(1),
                        ]),
                    ]),
                Infolists\Components\Section::make('Form Information')
                    ->icon('heroicon-m-information-circle')
                    ->schema([
                        Infolists\Components\ViewEntry::make('survey_data')
                            ->label('')
                            ->view('filament.infolists.service-report-survey')
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),

                Infolists\Components\Section::make('Audit Information')
                    ->icon('heroicon-m-information-circle')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('creator.name')
                                    ->label('Created By')
                                    ->placeholder('System'),

                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Created At')
                                    ->dateTime('d M Y, H:i'),

                                Infolists\Components\TextEntry::make('updater.name')
                                    ->label('Updated By')
                                    ->placeholder('No updates'),

                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->dateTime('d M Y, H:i'),
                            ]),
                    ])
                    ->collapsed(),
            ]);
    }
}
