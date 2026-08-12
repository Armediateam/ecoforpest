<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadResource\Pages;
use App\Filament\Resources\LeadResource\RelationManagers;
use App\Models\Lead;
use App\Models\Status;
use Carbon\Carbon;
use Filament\Forms\Components\Tabs;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Enums\FiltersLayout;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class LeadResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Lead::class;

    protected static ?string $navigationIcon = 'heroicon-o-phone-arrow-up-right';

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'restore',
            'restore_any',
            'replicate',
            'reorder',
            'delete',
            'delete_any',
            'force_delete',
            'force_delete_any',
            // custom
            'convert',
        ];
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        // Left Column
                        Forms\Components\Grid::make(1)
                            ->schema([
                                // Lead Management Section
                                Forms\Components\Section::make('Lead Management')
                                    ->description('Manage lead status, assignment and categorization')
                                    ->icon('heroicon-o-user-circle')
                                    ->schema([
                                        Forms\Components\Select::make('status_id')
                                            ->label('Status')
                                            ->relationship('status', 'name')
                                            ->createOptionForm([
                                                Forms\Components\Section::make()
                                                    ->schema([
                                                        Forms\Components\TextInput::make('name')
                                                            ->columnSpanFull()
                                                            ->required(),
                                                    ])
                                            ])
                                            ->searchable()
                                            ->preload()
                                            ->required(),
                                        Forms\Components\Select::make('source_id')
                                            ->label('Lead Source')
                                            ->relationship('source', 'name')
                                            ->createOptionForm([
                                                Forms\Components\Section::make()
                                                    ->schema([
                                                        Forms\Components\TextInput::make('name')
                                                            ->columnSpanFull()
                                                            ->required(),
                                                    ])
                                            ])
                                            ->searchable()
                                            ->preload(),
                                        Forms\Components\Select::make('assigned')
                                            ->label('Assigned To')
                                            ->relationship('assigned', 'name')
                                            ->preload()
                                            ->createOptionForm([
                                                Forms\Components\Section::make()
                                                    ->schema([
                                                        Forms\Components\TextInput::make('name')
                                                            ->label('Name')
                                                            ->required()
                                                            ->columnSpanFull()
                                                            ->maxLength(255),

                                                        Forms\Components\TextInput::make('email')
                                                            ->label('Email Address')
                                                            ->email()
                                                            ->required()
                                                            ->unique(ignoreRecord: true)
                                                            ->maxLength(255),

                                                        Forms\Components\TextInput::make('password')
                                                            ->label('Password')
                                                            ->password()
                                                            ->required()
                                                            ->revealable()
                                                            ->maxLength(255),
                                                    ])->columns(2)
                                            ])
                                            ->default(auth()->user()->id),
                                        Forms\Components\Select::make('tags')
                                            ->label('Tags')
                                            ->relationship('tags', 'name')
                                            ->createOptionForm([
                                                Forms\Components\Section::make()
                                                    ->schema([
                                                        Forms\Components\TextInput::make('name')
                                                            ->columnSpanFull()
                                                            ->required(),
                                                    ])
                                            ])
                                            ->preload()
                                            ->searchable()
                                            ->multiple(),
                                    ])->columns(2)->collapsible(),

                                // Basic Information Section
                                Forms\Components\Section::make('Basic Information')
                                    ->description('Lead personal and contact information')
                                    ->icon('heroicon-o-identification')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Full Name')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('email')
                                            ->label('Email Address')
                                            ->email()
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('company')
                                            ->label('Company Name')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('website')
                                            ->label('Website URL')
                                            ->url()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('phone')
                                            ->label('Phone Number')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('position')
                                            ->label('Position')
                                            ->required()
                                    ])->columns(2),

                                // Lead Value & Business Section
                                Forms\Components\Section::make('Lead Value & Business Details')
                                    ->description('Financial and business-related information')
                                    ->icon('heroicon-o-currency-dollar')
                                    ->schema([
                                        Forms\Components\TextInput::make('lead_value')
                                            ->label('Lead Value')
                                            ->required()
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->hint('Estimated value of this lead'),
                                        Forms\Components\DateTimePicker::make('date_contacted')
                                            ->label('Last Contacted')
                                            ->required()
                                            ->hint('When was this lead last contacted'),
                                        Forms\Components\TextInput::make('default_language')
                                            ->label('Preferred Language')
                                            ->default('Indonesia')
                                            ->maxLength(255)
                                            ->hint('Preferred communication language'),
                                        // Forms\Components\Toggle::make('is_public')
                                        //     ->label('Public Lead')
                                        //     ->helperText('Make this lead visible to all team members')
                                        //     ->inline(false),
                                    ])->columns(2)->collapsible(),
                            ])
                            ->columnSpan(1),

                        // Right Column
                        Forms\Components\Grid::make(1)
                            ->schema([
                                // Address & Location Section
                                Forms\Components\Section::make('Address & Location')
                                    ->description('Geographic information and coordinates')
                                    ->icon('heroicon-o-map-pin')
                                    ->schema([
                                        Forms\Components\Textarea::make('address')
                                            ->label('Street Address')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                        Forms\Components\TextInput::make('city')
                                            ->label('City')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('state')
                                            ->label('State/Province')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\Select::make('country_id')
                                            ->label('Country')
                                            ->relationship('country', 'name')
                                            ->createOptionForm([
                                                Forms\Components\Section::make()
                                                    ->schema([
                                                        Forms\Components\TextInput::make('name')
                                                            ->columnSpanFull()
                                                            ->required(),
                                                    ])
                                            ])
                                            ->searchable()
                                            ->preload()
                                            ->required(),
                                        Forms\Components\TextInput::make('zip_code')
                                            ->label('ZIP/Postal Code')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('google_maps_url')
                                            ->label('Google Maps URL')
                                            ->prefixIcon('heroicon-o-map-pin')
                                            ->columnSpanFull()
                                            ->maxLength(255),
                                    ])->columns(2)->collapsible(),

                                Forms\Components\Section::make('Additional Notes')
                                    ->description('Extra information and comments about this lead')
                                    ->icon('heroicon-o-document-text')
                                    ->schema([
                                        Forms\Components\Textarea::make('description')
                                            ->label('Description/Notes')
                                            ->rows(4)
                                            ->columnSpanFull()
                                            ->hint('Any additional information about this lead'),
                                    ])->collapsible()->collapsed()->columnSpanFull(),
                            ])
                            ->columnSpan(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Lead Status with Badge
                Tables\Columns\TextColumn::make('status.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Converted' => 'success',
                        'Pending' => 'warning',
                        default => 'gray',
                    })
                    ->icon(fn(string $state): string => match ($state) {
                        'Converted' => 'heroicon-o-check-circle',
                        'Pending' => 'heroicon-o-clock',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->sortable()
                    ->searchable(),

                // Lead Source
                Tables\Columns\TextColumn::make('source.name')
                    ->label('Source')
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-o-globe-alt')
                    ->sortable()
                    ->searchable()
                    ->placeholder('Unknown'),

                // Lead Name with Icon
                Tables\Columns\TextColumn::make('name')
                    ->label('Lead Name')
                    ->icon('heroicon-o-user')
                    ->iconColor('primary')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                // Company with Icon
                Tables\Columns\TextColumn::make('company')
                    ->label('Company')
                    ->icon('heroicon-o-building-office')
                    ->iconColor('gray')
                    ->searchable()
                    ->sortable(),

                // Lead Value with Currency Format
                Tables\Columns\TextColumn::make('lead_value')
                    ->label('Lead Value')
                    ->money('IDR')
                    ->icon('heroicon-o-currency-dollar')
                    ->iconColor('success')
                    ->sortable()
                    ->alignEnd(),

                // Contact Information
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->icon('heroicon-o-envelope')
                    ->iconColor('blue')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Email copied!')
                    ->url(fn($record) => "mailto:{$record->email}")
                    ->openUrlInNewTab(false),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->icon('heroicon-o-phone')
                    ->iconColor('green')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Phone number copied!')
                    ->placeholder('No phone'),

                // Assigned User
                Tables\Columns\TextColumn::make('assigned.name')
                    ->label('Assigned To')
                    ->icon('heroicon-o-user-circle')
                    ->iconColor('info')
                    ->sortable()
                    ->searchable()
                    ->placeholder('Unassigned')
                    ->color(fn($state) => $state ? 'primary' : 'gray'),

                // Last Contacted
                Tables\Columns\TextColumn::make('date_contacted')
                    ->label('Last Contact')
                    ->dateTime('M j, Y g:i A')
                    ->icon('heroicon-o-calendar')
                    ->iconColor('purple')
                    ->sortable()
                    ->since()
                    // ->description(function($record) {
                    //     if (!$record->date_contacted) {
                    //         return 'Never contacted';
                    //     }

                    //     try {
                    //         // Ensure it's a Carbon instance
                    //         $date = $record->date_contacted instanceof Carbon 
                    //             ? $record->date_contacted 
                    //             : Carbon::parse($record->date_contacted);
                    //         return 'Last contacted ' . $date->diffForHumans();
                    //     } catch (\Exception $e) {
                    //         return 'Invalid date';
                    //     }
                    // })
                    ->placeholder('Never contacted'),

                // Public Status
                // Tables\Columns\IconColumn::make('is_public')
                //     ->label('Public')
                //     ->boolean()
                //     ->trueIcon('heroicon-o-eye')
                //     ->falseIcon('heroicon-o-eye-slash')
                //     ->trueColor('success')
                //     ->falseColor('gray')
                //     ->tooltip(fn($state) => $state ? 'Visible to all team members' : 'Private lead'),

                // Timestamps (Hidden by default)
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->since(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->since(),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->since(),
            ])
            ->defaultSort('created_at', 'desc')
            ->persistSortInSession()
            ->persistSearchInSession()
            ->searchOnBlur()
            ->striped()
            ->filters([
                Tables\Filters\TrashedFilter::make(),

                Tables\Filters\SelectFilter::make('status')
                    ->relationship('status', 'name')
                    ->multiple()
                    ->preload(),

                Tables\Filters\SelectFilter::make('source')
                    ->relationship('source', 'name')
                    ->multiple()
                    ->preload(),

                Tables\Filters\SelectFilter::make('assigned')
                    ->relationship('assigned', 'name')
                    ->multiple()
                    ->preload(),

                Tables\Filters\Filter::make('high_value')
                    ->label('High Value Leads')
                    ->query(fn($query) => $query->where('lead_value', '>=', 10000000))
                    ->toggle(),

                // Tables\Filters\TernaryFilter::make('is_public')
                //     ->label('Public Status')
                //     ->placeholder('All leads')
                //     ->trueLabel('Public leads only')
                //     ->falseLabel('Private leads only'),

                Tables\Filters\Filter::make('recent_contact')
                    ->label('Recently Contacted')
                    ->query(fn($query) => $query->where('date_contacted', '>=', now()->subDays(7)))
                    ->toggle(),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->deferFilters()
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->visible(fn(): bool => auth()->user()->can('view_lead')),
                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil')
                        ->color('warning')
                        ->visible(fn($record): bool => auth()->user()->can('update_lead') && ! $record->trashed()),
                    Tables\Actions\DeleteAction::make()
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->visible(fn($record): bool => auth()->user()->can('delete_lead') && ! $record->trashed()),
                    Tables\Actions\RestoreAction::make()
                        ->icon('heroicon-o-arrow-path')
                        ->visible(fn($record): bool => auth()->user()->can('restore_lead') && $record->trashed()),
                    Tables\Actions\ForceDeleteAction::make()
                        ->icon('heroicon-o-x-circle')
                        ->visible(fn($record): bool => auth()->user()->can('force_delete_lead') && $record->trashed()),
                    Tables\Actions\Action::make('followUp')
                        ->label('Follow Up')
                        ->icon('heroicon-o-chat-bubble-left-ellipsis')
                        ->color('primary')
                        ->form([
                            Forms\Components\Textarea::make('message')
                                ->placeholder('Type your message here...')
                                ->required(),
                        ])
                        ->action(function (array $data, $record) {
                            $phoneNumber = $record->phone;

                            $phoneNumber = \Illuminate\Support\Str::start(preg_replace('/[^0-9]/', '', $phoneNumber), '62');

                            $message = $data['message'];

                            $encodedMessage = rawurlencode($message);

                            $url = "https://wa.me/{$phoneNumber}?text={$encodedMessage}";

                            return redirect()->to($url);
                        })
                        ->visible(function ($record): bool {
                            $isConverted = $record->status->name === 'Converted';

                            return !$isConverted && auth()->user()->can('update_lead') && !$record->trashed();
                        })
                ])
                    ->label('Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button()
                    ->visible(
                        fn(): bool =>
                        auth()->user()->can('view_lead') ||
                            auth()->user()->can('update_lead') ||
                            auth()->user()->can('delete_lead')
                    ),
            ], position: ActionsPosition::BeforeCells)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->icon('heroicon-o-trash')
                        ->visible(fn(): bool => auth()->user()->can('delete_any_lead')),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->icon('heroicon-o-x-circle')
                        ->visible(fn(): bool => auth()->user()->can('force_delete_any_lead')),
                    Tables\Actions\RestoreBulkAction::make()
                        ->icon('heroicon-o-arrow-path')
                        ->visible(fn(): bool => auth()->user()->can('restore_any_lead')),

                    // Custom bulk actions
                    Tables\Actions\BulkAction::make('assign_to_me')
                        ->label('Assign to Me')
                        ->icon('heroicon-o-user-plus')
                        ->color('primary')
                        ->visible(fn(): bool => auth()->user()->can('update_lead'))
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['assigned' => auth()->id()]);
                            });
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Assign selected leads to yourself?')
                        ->modalDescription('This will assign all selected leads to your account.')
                        ->modalSubmitActionLabel('Assign to Me'),

                    Tables\Actions\BulkAction::make('mark_as_hot')
                        ->label('Mark as Hot')
                        ->icon('heroicon-o-fire')
                        ->color('danger')
                        ->visible(fn(): bool => auth()->user()->can('update_lead'))
                        ->action(function ($records) {
                            $hotStatus = Status::where('name', 'Hot')->first();
                            if ($hotStatus) {
                                $records->each(function ($record) use ($hotStatus) {
                                    $record->update(['status_id' => $hotStatus->id]);
                                });
                            }
                        })
                        ->requiresConfirmation(),
                ])
                    ->visible(
                        fn(): bool =>
                        auth()->user()->can('delete_any_lead') ||
                            auth()->user()->can('force_delete_any_lead') ||
                            auth()->user()->can('restore_any_lead') ||
                            auth()->user()->can('update_lead')
                    ),
            ])
            ->emptyStateIcon('heroicon-o-users')
            ->emptyStateHeading('No leads yet')
            ->emptyStateDescription('Once you add your first lead, it will appear here.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Create your first lead')
                    ->icon('heroicon-o-plus'),
            ]);
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
            'index' => Pages\ListLeads::route('/'),
            'create' => Pages\CreateLead::route('/create'),
            'edit' => Pages\EditLead::route('/{record}/edit'),
            'view' => Pages\ViewLeadTabs::route('/{record}'),
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Grid::make([
                    'default' => 1,
                    'lg' => 2,
                ])
                    ->schema([
                        // Left Column: Basic Information & Lead Information
                        Grid::make(1)
                            ->schema([
                                Section::make('Basic Information')
                                    ->schema([
                                        Grid::make([
                                            'default' => 1,
                                            'sm' => 2,
                                        ])
                                            ->schema([
                                                TextEntry::make('name')
                                                    ->label('Name')
                                                    ->weight(FontWeight::Bold),

                                                TextEntry::make('email')
                                                    ->label('Email')
                                                    ->copyable()
                                                    ->icon('heroicon-o-envelope'),

                                                TextEntry::make('phone')
                                                    ->label('Phone')
                                                    ->copyable()
                                                    ->icon('heroicon-o-phone'),

                                                TextEntry::make('company')
                                                    ->label('Company')
                                                    ->icon('heroicon-o-building-office'),
                                            ]),
                                    ]),

                                Section::make('Lead Information')
                                    ->schema([
                                        Grid::make([
                                            'default' => 1,
                                            'sm' => 2,
                                        ])
                                            ->schema([
                                                TextEntry::make('status.name')
                                                    ->label('Status')
                                                    ->badge()
                                                    ->color(fn(string $state): string => match ($state) {
                                                        'Hot' => 'danger',
                                                        'Warm' => 'warning',
                                                        'Cold' => 'info',
                                                        default => 'gray',
                                                    }),

                                                TextEntry::make('source.name')
                                                    ->label('Source')
                                                    ->badge()
                                                    ->color('primary'),

                                                TextEntry::make('assigned.name')
                                                    ->label('Assigned To'),

                                                TextEntry::make('lead_value')
                                                    ->label('Lead Value')
                                                    ->money('IDR')
                                                    ->color('success'),

                                                TextEntry::make('date_contacted')
                                                    ->label('Date Contacted')
                                                    ->date(),

                                                // TextEntry::make('is_public')
                                                //     ->label('Public')
                                                //     ->formatStateUsing(fn(bool $state): string => $state ? 'Yes' : 'No')
                                                //     ->icon(fn(bool $state): string => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                                                //     ->iconColor(fn(bool $state): string => $state ? 'success' : 'danger'),
                                            ]),
                                    ]),

                                Section::make('Additional Information')
                                    ->schema([
                                        Grid::make([
                                            'default' => 1,
                                            'sm' => 2,
                                        ])
                                            ->schema([
                                                TextEntry::make('website')
                                                    ->label('Website')
                                                    ->url(function ($state) {
                                                        return $state == null ? '#' : $state;
                                                    })
                                                    ->openUrlInNewTab(),

                                                TextEntry::make('position')
                                                    ->label('Position'),

                                                TextEntry::make('default_language')
                                                    ->label('Default Language'),
                                            ]),
                                    ]),
                            ])
                            ->columnSpan(1),

                        // Right Column: Address Information & System Information
                        Grid::make(1)
                            ->schema([
                                Section::make('Address Information')
                                    ->schema([
                                        Grid::make([
                                            'default' => 1,
                                            'sm' => 2,
                                        ])
                                            ->schema([
                                                TextEntry::make('address')
                                                    ->label('Address')
                                                    ->columnSpanFull(),

                                                TextEntry::make('city')
                                                    ->label('City'),

                                                TextEntry::make('state')
                                                    ->label('State'),

                                                TextEntry::make('zip_code')
                                                    ->label('ZIP Code'),

                                                TextEntry::make('country.name')
                                                    ->label('Country'),

                                                TextEntry::make('google_maps_url')
                                                    ->label('Google Maps URL')
                                                    ->formatStateUsing(fn($state) => $state ? 'View Maps' : null)
                                                    ->badge()
                                                    ->url(fn($record) => $record->google_maps_url, true)
                                                    ->openUrlInNewTab()
                                                    ->color('primary')
                                                    ->icon('heroicon-m-link'),
                                            ]),
                                    ]),

                                Section::make('System Information')
                                    ->schema([
                                        Grid::make([
                                            'default' => 1,
                                            'sm' => 2,
                                        ])
                                            ->schema([
                                                TextEntry::make('created_at')
                                                    ->label('Created At')
                                                    ->dateTime()
                                                    ->since(),

                                                TextEntry::make('updated_at')
                                                    ->label('Updated At')
                                                    ->dateTime()
                                                    ->since(),
                                            ]),
                                    ]),
                            ])
                            ->columnSpan(1),
                    ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
