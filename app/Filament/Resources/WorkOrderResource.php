<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkOrderResource\Pages;
use App\Filament\Resources\WorkOrderResource\RelationManagers;
use App\Models\WorkOrder;
use App\Models\Package;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Lead;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\RawJs;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Enums\ActionsPosition;
use App\Filament\Clusters\WorkOrders;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Afsakar\LeafletMapPicker\LeafletMapPickerEntry;
use Afsakar\LeafletMapPicker\LeafletMapPicker;
use Filament\Notifications\Notification;
use FilamentTiptapEditor\Enums\TiptapOutput;
use FilamentTiptapEditor\TiptapEditor;
use Illuminate\Support\Facades\Log;

class WorkOrderResource extends Resource
{
    protected static ?string $model = WorkOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Work Orders';

    protected static ?string $cluster = WorkOrders::class;

    protected static ?string $modelLabel = 'Work Order';

    protected static ?string $pluralModelLabel = 'Work Orders';

    protected static ?int $navigationSort = 10;

    public static function generateRecurringDates($get, $set): void
    {
        if (!$get('is_recuring')) {
            $set('recurring_dates', []);
            return;
        }

        $workDate = $get('work_date') ? \Carbon\Carbon::parse($get('work_date')) : now();
        $workTime = $get('work_time') ?? '08:00';
        $repeatEvery = (int) $get('repeat_every') ?: 1;
        $repeatType = $get('repeat_type') ?: 'day';
        $repeatCycle = (int) $get('repeat_cycle') ?: 1;

        $dates = [];

        for ($i = 0; $i < $repeatCycle; $i++) {
            $dates[] = [
                'date' => $workDate->copy()->add(($i + 1) * $repeatEvery, $repeatType)->toDateString(),
                'time' => $workTime,
            ];
        }

        $set('recurring_dates', $dates);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        // KOLOM KIRI
                        Forms\Components\Group::make([
                            // Service & Package Information Section
                            Forms\Components\Section::make('Service & Package Information')
                                ->description('Select the service type and configure packages')
                                ->icon('heroicon-m-wrench-screwdriver')
                                ->schema([
                                    Forms\Components\Select::make('service_id')
                                        ->label('Service Type')
                                        ->placeholder('Select a service...')
                                        ->options(Service::all()->pluck('name', 'id'))
                                        ->required()
                                        ->live()
                                        ->searchable()
                                        ->preload()
                                        ->helperText('Choose the type of service for this work order')
                                        ->afterStateUpdated(function ($set, $get) {
                                            $serviceId = $get('service_id');
                                            $packages = Package::where('service_id', $serviceId)->get()->map(fn($package) => [
                                                'package_id' => $package->id,
                                                'price' => $package->max_price,
                                            ])->toArray();
                                            $set('workOrderPackage', $packages);
                                            $set('total', 0); // Reset total when service changes

                                            // Set tindakan dari service yang dipilih
                                            $service = Service::find($serviceId);
                                            $set('tindakan', $service?->tindakan ?? []);
                                        })
                                        ->columnSpanFull(),

                                    Forms\Components\Repeater::make('workOrderPackage')
                                        ->relationship('workOrderPackage')
                                        ->label('Service Packages')
                                        ->schema([
                                            Forms\Components\Select::make('package_id')
                                                ->label('Package')
                                                ->options(Package::all()->pluck('name', 'id'))
                                                ->live()
                                                ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                    if ($state) {
                                                        $package = Package::find($state);
                                                        $set('guarantee', $package?->guarantee ?? 0, true);
                                                    }
                                                }),
                                            Forms\Components\TextInput::make('price')
                                                ->label('Unit Price')
                                                ->prefix('Rp')
                                                ->mask(RawJs::make('$money($input, \',\')'))
                                                ->stripCharacters('.')
                                                ->live(debounce: '10s')
                                                ->numeric(),
                                            Forms\Components\TextInput::make('qty')
                                                ->label('Quantity')
                                                ->numeric()
                                                ->default(1)
                                                ->minValue(1)
                                                ->required()
                                                ->live(onBlur: true)
                                                ->suffix('unit(s)')
                                        ])
                                        ->addable(false)
                                        ->deletable(false)
                                        ->reorderable(false)
                                        ->visible(fn($get) => $get('service_id'))
                                        ->columns(3)
                                        ->live()
                                        ->afterStateUpdated(function (Get $get, Set $set) {
                                            $workOrderPackage = $get('workOrderPackage') ?? [];
                                            $total = collect($workOrderPackage)->sum(fn($item) => ((int) preg_replace('/[^0-9]/', '', $item['price'] ?? 0)) * ((int) preg_replace('/[^0-9]/', '', $item['qty'] ?? 0)));
                                            $set('total', $total);
                                        })
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make('total')
                                        ->label('Total Amount')
                                        ->prefix('Rp')
                                        ->mask(RawJs::make('$money($input, \',\')'))
                                        ->stripCharacters('.')
                                        ->numeric()
                                        ->default(0)
                                        ->helperText('Total will be calculated automatically based on package quantities')
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make('guarantee')
                                        ->label('Guarantee')
                                        ->default(0)
                                        ->helperText('Enter the warranty period (example: 1 Day, 1 Week, 1 Month, 1 Year, etc).')
                                        ->live()
                                        ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                            if ($state === null || $state === '') {
                                                $set('guarantee', 0);
                                            }
                                        })
                                        ->columnSpanFull(),
                                ])->columns(1)->collapsible(),



                            // Work Details Section

                            // Client Information Section
                            Forms\Components\Section::make('Client Information')
                                ->description('Select the client for this work order')
                                ->icon('heroicon-m-user-group')
                                ->schema([
                                    Forms\Components\Select::make('related')
                                        ->label('Client Type')
                                        ->live()
                                        ->options([
                                            'customer' => 'Existing Customer',
                                            'lead' => 'Potential Lead',
                                        ])
                                        ->default('customer')
                                        ->required()
                                        ->helperText('Choose whether this is for an existing customer or a potential lead')
                                        ->columnSpanFull(),

                                    Forms\Components\Select::make('customer_id')
                                        ->label('Select Customer')
                                        ->visible(fn($get) => $get('related') === 'customer')
                                        ->relationship('customer', 'name')
                                        ->getOptionLabelFromRecordUsing(fn(Customer $record) => "{$record->name} - {$record->company}")
                                        ->searchable(['name', 'company'])
                                        ->preload()
                                        ->placeholder('Choose a customer...')
                                        ->helperText('Select from existing customers')
                                        ->columnSpanFull(),

                                    Forms\Components\Select::make('lead_id')
                                        ->label('Select Lead')
                                        ->visible(fn($get) => $get('related') === 'lead')
                                        ->relationship('lead', 'name')
                                        ->getOptionLabelFromRecordUsing(fn(Lead $record) => "{$record->name} - {$record->company}")
                                        ->searchable(['name', 'company'])
                                        ->preload()
                                        ->placeholder('Choose a lead...')
                                        ->helperText('Select from potential leads')
                                        ->columnSpanFull(),
                                ])->columns(1)->collapsible(),
                            Forms\Components\Section::make('Assignment & Status')
                                ->description('Assign worker and set status')
                                ->icon('heroicon-m-user-plus')
                                ->schema([
                                    Forms\Components\Select::make('assigned_id')
                                        ->label('Assigned Worker')
                                        ->options(fn() => \App\Models\Employee::all()->pluck('name', 'id'))
                                        ->searchable()
                                        ->preload()
                                        ->placeholder('Select a worker...')
                                        ->helperText('Choose the worker who will be responsible for this work order')
                                        ->live()
                                        ->afterStateUpdated(function (Set $set, $state) {
                                            if ($state) {
                                                $set('status', 'Assigned');
                                            }
                                        })
                                        ->columnSpanFull(),

                                    Forms\Components\Select::make('helpers')
                                        ->label('Assigned Helpers')
                                        ->relationship('helpers', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->multiple()
                                        ->placeholder('Select a helper...')
                                        ->helperText('Choose the helpers who will be responsible for this work order')
                                        ->columnSpanFull(),

                                    Forms\Components\Select::make('status')
                                        ->label('Status')
                                        ->options(array_combine(WorkOrder::$statuses, WorkOrder::$statuses))
                                        ->required()
                                        ->helperText('Current status of the work order')
                                        ->columnSpanFull(),
                                ])->columns(2)->collapsible(),
                        ]),

                        // KOLOM KANAN
                        Forms\Components\Group::make([
                            // Location & Address Section
                            Forms\Components\Section::make('Location & Address Information')
                                ->description('Specify the work location and address details')
                                ->icon('heroicon-m-map-pin')
                                ->schema([
                                    Forms\Components\Textarea::make('alamat')
                                        ->label('Full Address')
                                        ->placeholder('Enter the complete address where work will be performed...')
                                        ->required()
                                        ->rows(3)
                                        ->columnSpanFull(),

                                    LeafletMapPicker::make('position')
                                        ->label('Location on Map')
                                        ->helperText('Click on the map to set location or search for an address')
                                        ->height('400px')
                                        ->defaultLocation(function ($record) {
                                            if (isset($record->position[0]['latitude']) && isset($record->position[0]['longitude'])) {
                                                return [$record->position[0]['latitude'], $record->position[0]['longitude']];
                                            }
                                            return [-8.6210885, 115.184828]; // Bali coordinates
                                        })
                                        ->defaultZoom(20)
                                        ->draggable()
                                        ->clickable()
                                        ->myLocationButtonLabel('Go to My Location')
                                        ->tileProvider('openstreetmap')
                                        ->hideTileControl()
                                        ->afterStateUpdated(function ($state) {
                                            Log::info('LeafletMapPicker state updated:', ['state' => $state]);
                                        })
                                        ->live()
                                        ->columnSpanFull(),

                                    Forms\Components\Textarea::make('address_note')
                                        ->label('Additional Address Notes')
                                        ->placeholder('Landmarks, building details, access instructions, etc...')
                                        ->rows(2)
                                        ->helperText('Provide additional details to help locate the address')
                                        ->columnSpanFull(),
                                ])->columns(1)->collapsible(),

                            // Work Schedule Section
                            Forms\Components\Section::make('Work Schedule')
                                ->description('Set the work date, time, and recurring options')
                                ->icon('heroicon-m-calendar-days')
                                ->schema([
                                    Forms\Components\DatePicker::make('work_date')
                                        ->label('Work Date')
                                        ->required()
                                        ->native(false)
                                        ->closeOnDateSelection()
                                        ->helperText('Select the date when work should be performed')
                                        ->live()
                                        ->afterStateUpdated(fn($state, $get, $set) => self::generateRecurringDates($get, $set)),

                                    Forms\Components\TimePicker::make('work_time')
                                        ->label('Work Time')
                                        ->required()
                                        ->native(false)
                                        ->live()
                                        ->helperText('Select the preferred time for work')
                                        ->afterStateUpdated(fn($state, $get, $set) => self::generateRecurringDates($get, $set)),

                                    Forms\Components\Toggle::make('is_recuring')
                                        ->label('Recurring Work Order')
                                        ->helperText('Enable if this work needs to be repeated regularly')
                                        ->live()
                                        ->columnSpanFull(),

                                    Forms\Components\Grid::make(3)
                                        ->visible(fn($get): bool => $get('is_recuring'))
                                        ->schema([
                                            Forms\Components\TextInput::make('repeat_every')
                                                ->label('Repeat Every')
                                                ->numeric()
                                                ->minValue(1)
                                                ->default(1)
                                                ->required()
                                                ->live()
                                                ->helperText('How often to repeat')
                                                ->afterStateUpdated(fn($state, $get, $set) => self::generateRecurringDates($get, $set)),

                                            Forms\Components\Select::make('repeat_type')
                                                ->label('Repeat Type')
                                                ->options([
                                                    'day' => 'Day(s)',
                                                    'week' => 'Week(s)',
                                                    'month' => 'Month(s)',
                                                    'year' => 'Year(s)'
                                                ])
                                                ->required()
                                                ->live()
                                                ->helperText('Time unit for repetition')
                                                ->afterStateUpdated(fn($state, $get, $set) => self::generateRecurringDates($get, $set)),

                                            Forms\Components\TextInput::make('repeat_cycle')
                                                ->label('Number of Cycles')
                                                ->numeric()
                                                ->minValue(1)
                                                ->live()
                                                ->helperText('How many times to repeat')
                                                ->afterStateUpdated(fn($state, $get, $set) => self::generateRecurringDates($get, $set)),
                                        ]),

                                    // Recurring Dates Grid
                                    Forms\Components\Repeater::make('recurring_dates')
                                        ->label('Next Recurring Dates')
                                        ->helperText('These are the upcoming recurring dates after the initial work date')
                                        ->schema([
                                            Forms\Components\DatePicker::make('date')
                                                ->label('Next Date')
                                                ->required()
                                                ->native(false)
                                                ->closeOnDateSelection(),

                                            Forms\Components\TimePicker::make('time')
                                                ->label('Next Time')
                                                ->required()
                                                ->native(false)
                                                ->closeOnDateSelection(),
                                        ])
                                        ->default([])
                                        ->addable(false)
                                        ->deletable(false)
                                        ->visible(fn($get) => $get('is_recuring')),
                                ])->columns(1)->collapsible(),

                            // Assignment & Status Section

                        ]),
                    ]),
                Forms\Components\Section::make('Scope of Works')
                    ->description('Describe the specific work to be performed, requirements, and any special instructions...')
                    ->icon('heroicon-m-document-text')
                    ->schema([
                        Forms\Components\TextInput::make('target_pest')
                            ->label('Target Pest')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Repeater::make('tindakan')
                            ->label('Tindakan')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Tindakan')
                                    ->required(),
                                Forms\Components\TextInput::make('description')
                                    ->label('Deskripsi'),
                            ])
                            ->addable()
                            ->deletable()
                            ->reorderable()
                            ->default(fn($get) => \App\Models\Service::find($get('service_id'))?->tindakan ?? [])
                            ->columnSpanFull(),
                        Forms\Components\Select::make('scope_of_work_template_id')
                            ->label('Use Autofill Template')
                            ->options(fn() => \App\Models\ScopeOfWorkTemplate::all()->pluck('name', 'id'))
                            ->searchable()
                            ->reactive()
                            ->helperText('Select a template to autofill Scope of Works')
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                if ($state) {
                                    $template = \App\Models\ScopeOfWorkTemplate::find($state);
                                    if ($template) {
                                        $content = self::renderScopeOfWorkTemplate($template->content, $get);
                                        $set('detail_work', $content);
                                    }
                                }
                            }),
                        TiptapEditor::make('detail_work')
                            ->output(TiptapOutput::Html)
                            ->extraInputAttributes(['style' => 'min-height: 54rem;'])
                            ->required()
                            ->columnSpanFull()
                            ->reactive(),
                    ]),

                // Progress History Section (Edit Mode Only) - Full Width
                Forms\Components\Section::make('Progress History')
                    ->description('Track work order progress and updates')
                    ->icon('heroicon-m-clock')
                    ->schema([
                        Forms\Components\Repeater::make('progress')
                            ->relationship('progress')
                            ->schema([
                                Forms\Components\TextInput::make('progress_status')
                                    ->label('Status')
                                    ->readOnly(),
                                Forms\Components\Textarea::make('notes')
                                    ->label('Notes')
                                    ->readOnly(),
                                Forms\Components\TextInput::make('completed_at')
                                    ->label('Completed At')
                                    ->readOnly(),
                                Forms\Components\TextInput::make('completed_by')
                                    ->label('Completed By')
                                    ->readOnly(),
                            ])
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->columns(2)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn($context) => $context === 'edit')
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // ID Column with clickable link
                // Tables\Columns\TextColumn::make('id')
                //     ->label('ID')
                //     ->sortable()
                //     ->searchable()
                //     ->weight(FontWeight::Bold)
                //     ->color('primary')
                //     ->url(fn($record) => WorkOrderResource::getUrl('edit', ['record' => $record]))
                //     ->tooltip('Click to view details'),

                // Service Information
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service')
                    ->sortable()
                    ->searchable()
                    ->icon('heroicon-m-wrench-screwdriver')
                    ->tooltip('Service type'),

                // Enhanced Status Badge
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->icon(fn($state) => match ($state) {
                        'Open' => 'heroicon-m-folder-open',
                        'Pending' => 'heroicon-m-clock',
                        'Hold Confirm' => 'heroicon-m-exclamation-triangle',
                        'Confirm' => 'heroicon-m-check-circle',
                        'Assigned' => 'heroicon-m-user-plus',
                        'On Progress' => 'heroicon-m-cog-6-tooth',
                        'Closed' => 'heroicon-m-check-circle',
                        'Cancelled' => 'heroicon-m-x-circle',
                        default => 'heroicon-m-ellipsis-horizontal-circle',
                    })
                    ->color(fn($state) => match ($state) {
                        'Open' => 'blue',      // Biru
                        'Pending' => 'violet',   // Violet
                        'Hold Confirm' => 'warning', // Oranye
                        'Confirm' => 'success',   // Hijau
                        'Assigned' => 'teal',  // Teal
                        'On Progress' => 'sky',   // Biru Langit
                        'Closed' => 'gray',    // Abu-abu
                        'Cancelled' => 'danger', // Merah
                    })
                    ->sortable()
                    ->tooltip('Current work order status'),

                // Client Information (Combined)
                Tables\Columns\TextColumn::make('client')
                    ->label('Client')
                    ->getStateUsing(function ($record) {
                        if ($record->customer) {
                            return $record->customer->name;
                        } elseif ($record->lead) {
                            return $record->lead->name . ' (Lead)';
                        }
                        return '-';
                    })
                    ->icon(fn($record) => $record->customer ? 'heroicon-m-user' : 'heroicon-m-user-plus')
                    ->color(fn($record) => $record->customer ? 'success' : 'warning')
                    ->sortable()
                    ->tooltip('Client information'),

                // Work Date and Time (Combined)
                Tables\Columns\TextColumn::make('work_schedule')
                    ->label('Schedule')
                    ->getStateUsing(function ($record) {
                        $date = $record->work_date ? \Carbon\Carbon::parse($record->work_date)->format('d M Y') : '-';
                        $time = $record->work_time ? $record->work_time : '-';
                        return "{$date} at {$time}";
                    })
                    ->icon('heroicon-m-calendar-days')
                    ->sortable(['work_date', 'work_time'])
                    ->tooltip('Scheduled date and time'),

                // Total Amount (Formatted)
                Tables\Columns\TextColumn::make('total')
                    ->label('Amount')
                    ->money('IDR')
                    ->sortable()
                    ->icon('heroicon-m-banknotes')
                    ->color('success')
                    ->weight(FontWeight::SemiBold)
                    ->tooltip('Total work order amount'),

                // Work Description (Truncated)
                // Tables\Columns\TextColumn::make('detail_work')
                //     ->label('Description')
                //     ->limit(50)
                //     ->tooltip(fn($record) => $record->detail_work)
                //     ->searchable()
                //     ->toggleable()
                //     ->wrap(),

                // Assigned Worker
                Tables\Columns\TextColumn::make('assigned.name')
                    ->label('Assigned Worker')
                    ->badge()
                    ->placeholder('Unassigned')
                    ->icon('heroicon-m-user-plus')
                    ->color(fn($state) => $state ? 'success' : 'gray')
                    ->sortable()
                    ->tooltip('Assigned worker')
                    ->toggleable(),

                // Assigned Helper
                Tables\Columns\TextColumn::make('helpers.name')
                    ->label('Assigned To')
                    ->badge()
                    ->placeholder('Unassigned')
                    ->icon('heroicon-m-user-plus')
                    ->color(fn($state) => $state ? 'success' : 'gray')
                    ->sortable()
                    ->tooltip('Assigned Helper')
                    ->toggleable(),

                // Recurring Indicator
                Tables\Columns\IconColumn::make('is_recuring')
                    ->label('Recurring')
                    ->boolean()
                    ->trueIcon('heroicon-m-arrow-path')
                    ->falseIcon('heroicon-m-minus-circle')
                    ->trueColor('info')
                    ->falseColor('gray')
                    ->tooltip(fn($record) => $record->is_recuring
                        ? "Repeats every {$record->repeat_every} {$record->repeat_type}(s)"
                        : 'One-time work order')
                    ->toggleable(),

                // Location Address (Truncated)
                Tables\Columns\TextColumn::make('alamat')
                    ->label('Location')
                    ->limit(30)
                    ->tooltip(fn($record) => $record->alamat)
                    ->icon('heroicon-m-map-pin')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Created Date
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Work order creation date'),
            ])
            ->filters([
                // Status Filter
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(array_combine(WorkOrder::$statuses, WorkOrder::$statuses))
                    ->multiple()
                    ->placeholder('All statuses'),

                // Service Filter
                Tables\Filters\SelectFilter::make('service')
                    ->relationship('service', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->placeholder('All services'),

                // Client Type Filter
                Tables\Filters\SelectFilter::make('related')
                    ->label('Client Type')
                    ->options([
                        'customer' => 'Customer',
                        'lead' => 'Lead',
                    ])
                    ->placeholder('All client types'),

                // Customer Filter
                Tables\Filters\SelectFilter::make('customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->placeholder('All customers'),

                // Lead Filter
                Tables\Filters\SelectFilter::make('lead')
                    ->relationship('lead', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->placeholder('All leads'),

                // Date Range Filter
                Tables\Filters\Filter::make('work_date_range')
                    ->label('Work Date Range')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date')
                            ->placeholder('Select start date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date')
                            ->placeholder('Select end date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('work_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('work_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = 'From ' . \Carbon\Carbon::parse($data['from'])->toFormattedDateString();
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = 'Until ' . \Carbon\Carbon::parse($data['until'])->toFormattedDateString();
                        }
                        return $indicators;
                    }),

                // Worker Filter
                Tables\Filters\SelectFilter::make('assigned')
                    ->relationship('assigned', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('All workers'),

                //Assigned Helpers Filter
                Tables\Filters\SelectFilter::make('helpers')
                    ->relationship('helpers', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->placeholder('All helpers'),

                // Amount Range Filter
                Tables\Filters\Filter::make('amount_range')
                    ->label('Amount Range')
                    ->form([
                        Forms\Components\TextInput::make('min_amount')
                            ->label('Minimum Amount')
                            ->numeric()
                            ->prefix('Rp'),
                        Forms\Components\TextInput::make('max_amount')
                            ->label('Maximum Amount')
                            ->numeric()
                            ->prefix('Rp'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_amount'],
                                fn(Builder $query, $amount): Builder => $query->where('total', '>=', $amount),
                            )
                            ->when(
                                $data['max_amount'],
                                fn(Builder $query, $amount): Builder => $query->where('total', '<=', $amount),
                            );
                    }),

                // Recurring Filter
                Tables\Filters\TernaryFilter::make('is_recuring')
                    ->label('Recurring Orders')
                    ->placeholder('All orders')
                    ->trueLabel('Recurring only')
                    ->falseLabel('One-time only'),

                // Trash Filter
                Tables\Filters\TrashedFilter::make()
                    ->label('Include Deleted'),

            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->icon('heroicon-m-eye')
                        ->color('info'),
                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-m-pencil-square')
                        ->color('warning'),
                    Tables\Actions\DeleteAction::make()
                        ->icon('heroicon-m-trash')
                        ->color('danger'),
                    Tables\Actions\Action::make('change_status')
                        ->label('Change Status')
                        ->icon('heroicon-m-arrow-path')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('Change Status')
                                ->default(fn($record) => $record->status)
                                ->options(array_combine(WorkOrder::$statuses, WorkOrder::$statuses))
                                ->required(),
                        ])
                        ->action(function (array $data, $record) {
                            $record->update(['status' => $data['status']]);

                            Notification::make()
                                ->title('Work Order Status Updated Successfully')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalDescription('This will update the status of the work order.'),
                    Tables\Actions\Action::make('reschedule')
                        ->label('Reschedule')
                        ->icon('heroicon-m-calendar')
                        ->form([
                            Forms\Components\DatePicker::make('work_date')
                                ->label('New Work Date')
                                ->required()
                                ->default(fn($record) => $record->work_date)
                                ->native(false)
                                ->closeOnDateSelection(),
                            Forms\Components\TimePicker::make('work_time')
                                ->label('New Work Time')
                                ->default(fn($record) => $record->work_time)
                                ->required()
                                ->native(false),
                        ])
                        ->action(function (array $data, $record) {
                            $record->update([
                                'work_date' => $data['work_date'],
                                'work_time' => $data['work_time'],
                            ]);

                            Notification::make()
                                ->title('Work Order Rescheduled Successfully')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalDescription('This will reschedule the work order to a new date and time.')
                        ->successNotificationTitle('Work order rescheduled successfully'),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button()
                    ->label('Actions'),
            ], position: ActionsPosition::BeforeCells)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->icon('heroicon-m-trash'),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->icon('heroicon-m-trash'),
                    Tables\Actions\RestoreBulkAction::make()
                        ->icon('heroicon-m-arrow-uturn-left'),

                    // Custom Bulk Actions
                    // Tables\Actions\BulkAction::make('bulk_assign')
                    //     ->label('Bulk Assign Worker')
                    //     ->icon('heroicon-m-user-plus')
                    //     ->color('primary')
                    //     ->form([
                    //         Forms\Components\Select::make('assigned_id')
                    //             ->label('Select Worker')
                    //             ->relationship('assigned', 'name')
                    //             ->required()
                    //             ->searchable()
                    //             ->preload(),
                    //     ])
                    //     ->action(function (array $data, $records) {
                    //         foreach ($records as $record) {
                    //             $record->update([
                    //                 'assigned_id' => $data['assigned_id'],
                    //                 'status' => 'Assigned'
                    //             ]);
                    //         }
                    //     })
                    //     ->requiresConfirmation()
                    //     ->modalDescription('This will assign the selected worker to all selected work orders.')
                    //     ->successNotificationTitle('Workers assigned successfully'),

                    Tables\Actions\BulkAction::make('bulk_status_update')
                        ->label('Update Status')
                        ->icon('heroicon-m-arrow-path')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('New Status')
                                ->options(array_combine(WorkOrder::$statuses, WorkOrder::$statuses))
                                ->required(),
                        ])
                        ->action(function (array $data, $records) {
                            foreach ($records as $record) {
                                $record->update(['status' => $data['status']]);
                            }
                        })
                        ->requiresConfirmation()
                        ->modalDescription('This will update the status for all selected work orders.')
                        ->successNotificationTitle('Status updated successfully'),
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
            'index' => Pages\ListWorkOrders::route('/'),
            'create' => Pages\CreateWorkOrder::route('/create'),
            'view' => Pages\ViewWorkOrder::route('/{record}'),
            'edit' => Pages\EditWorkOrder::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['service', 'customer', 'lead', 'assigned']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['id', 'detail_work', 'alamat', 'customer.name', 'lead.name', 'service.name'];
    }

    public static function getGlobalSearchResultTitle($record): string
    {
        return "Work Order #{$record->id}";
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        $details = [];

        if ($record->service) {
            $details['Service'] = $record->service->name;
        }

        if ($record->customer) {
            $details['Customer'] = $record->customer->name;
        } elseif ($record->lead) {
            $details['Lead'] = $record->lead->name;
        }

        $details['Status'] = $record->status;
        $details['Date'] = $record->work_date?->format('d M Y');

        return $details;
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'Open')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() > 0 ? 'warning' : null;
    }

    // Helper function to replace template variables
    public static function renderScopeOfWorkTemplate($template, $get)
    {
        // Bisa menerima parameter visitNumber manual (untuk recurring)
        $visitNumber = $get('visit_number') ?? null;
        if (!$visitNumber) {
            // Hitung urutan kunjungan ke customer (ke berapa work order ini untuk customer tersebut)
            $customerId = $get('customer_id');
            $workOrderId = $get('id');
            if ($customerId) {
                $orders = \App\Models\WorkOrder::where('customer_id', $customerId)
                    ->orderBy('work_date')
                    ->orderBy('id')
                    ->get();
                if ($workOrderId) {
                    $visitNumber = $orders->search(function ($wo) use ($workOrderId) {
                        return $wo->id == $workOrderId;
                    });
                    $visitNumber = ($visitNumber !== false) ? $visitNumber + 1 : $orders->count() + 1;
                } else {
                    $visitNumber = $orders->count() + 1;
                }
            } else {
                $visitNumber = 1;
            }
        }
        $replace = [
            '{customer_name}' => $get('customer_id') ? (\App\Models\Customer::find($get('customer_id'))->name ?? '-') : ($get('lead_id') ? (\App\Models\Lead::find($get('lead_id'))->name ?? '-') : '-'),
            '{customer_email}' => $get('customer_id') ? (\App\Models\Customer::find($get('customer_id'))->email ?? '-') : ($get('lead_id') ? (\App\Models\Lead::find($get('lead_id'))->email ?? '-') : '-'),
            '{customer_phone}' => $get('customer_id') ? (\App\Models\Customer::find($get('customer_id'))->phone ?? '-') : ($get('lead_id') ? (\App\Models\Lead::find($get('lead_id'))->phone ?? '-') : '-'),
            '{customer_fulladdress}' => $get('alamat') ?? '-',
            '{customer_mapurl}' => $get('customer_id') ? (\App\Models\Customer::find($get('customer_id'))->google_maps_url ?? '-') : ($get('lead_id') ? (\App\Models\Lead::find($get('lead_id'))->google_maps_url ?? '-') : '-'),
            '{guarantee}' => $get('guarantee') ?: '-',
            '{total}' => $get('total') ?? 0,
            '{workorder_date}' => $get('work_date') ? \Carbon\Carbon::parse($get('work_date'))->format('d M Y') : '-',
            '{workorder_time}' => $get('work_time') ? (ltrim(\Carbon\Carbon::parse($get('work_time'))->format('H:i'), '0')) : '-',
            '{worker_name}' => $get('assigned_id') ? (\App\Models\Employee::find($get('assigned_id'))?->name ?? '-') : '-',
            '{helper_name}' => $get('helpers') ? (is_array($get('helpers')) ? implode(', ', array_map(fn($id) => \App\Models\Employee::find($id)?->name, $get('helpers'))) : (\App\Models\Employee::find($get('helpers'))?->name ?? '-')) : '-',
            '{visit}' => $visitNumber,
            '{target_pest}' => $get('target_pest') ?? '-',
            '{tindakan}' => is_array($get('tindakan'))
                ? implode('<br>', array_map(function ($t, $i) {
                    $name = $t['name'] ?? '';
                    $desc = $t['description'] ?? '';
                    if (!is_string($name)) $name = json_encode($name, JSON_UNESCAPED_UNICODE);
                    if (!is_string($desc)) $desc = json_encode($desc, JSON_UNESCAPED_UNICODE);
                    $name = htmlspecialchars((string)$name, ENT_QUOTES);
                    $desc = htmlspecialchars((string)$desc, ENT_QUOTES);
                    $num = $i + 1;
                    return "$num. $name" . ($desc ? "<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$desc" : '');
                }, $get('tindakan'), range(0, count($get('tindakan')) - 1)))
                : (is_string($get('tindakan')) ? nl2br(htmlspecialchars($get('tindakan'), ENT_QUOTES)) : '-'),
        ];

        // Ensure all replacement values are strings to prevent "Array to string conversion" error
        $replace = array_map(function ($value) {
            if (is_array($value)) {
                return json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            return (string) $value;
        }, $replace);

        return str_replace(array_keys($replace), array_values($replace), $template);
    }
}
