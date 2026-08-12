<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontWeight;
use Filament\Infolists\Components\Section;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Grid;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class CustomerResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

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
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        // Left column
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Section::make('Lead & Company')
                                    ->description('Main customer and company information.')
                                    ->schema([
                                        Forms\Components\Select::make('lead_id')
                                            ->label('Lead')
                                            ->relationship('lead', 'name'),
                                        Forms\Components\TextInput::make('company')
                                            ->label('Company')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\Select::make('customer_group_id')
                                            ->label('Customer Group')
                                            ->relationship('customerGroup', 'name')
                                            ->createOptionForm([
                                                Forms\Components\Section::make()
                                                    ->schema([
                                                        Forms\Components\TextInput::make('name')
                                                            ->columnSpanFull()
                                                            ->required(),
                                                    ])
                                            ]),
                                        Forms\Components\TextInput::make('default_language')
                                            ->label('Default Language')
                                            ->required()
                                            ->maxLength(255)
                                            ->default('Indonesia'),
                                        Forms\Components\Select::make('job_status')
                                            ->label('Job Status')
                                            ->options([
                                                'Regular' => 'Regular',
                                                'One Job' => 'One Job',
                                            ])
                                            ->placeholder('Select Job Status'),
                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Is Active')
                                            ->default(true)
                                            ->inline(false)
                                            ->required(),
                                    ])
                                    ->columns(2),
                                Forms\Components\Section::make('Contact')
                                    ->description('Customer contact information.')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Name')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('phone')
                                            ->label('Phone')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('email')
                                            ->label('Email')
                                            ->email()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('website')
                                            ->label('Website')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('position')
                                            ->label('Position')
                                            ->maxLength(255),
                                    ])
                                    ->columns(2),
                            ]),
                        // Right column
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Section::make('Address')
                                    ->description('Customer address details.')
                                    ->schema([
                                        Forms\Components\Textarea::make('address')
                                            ->label('Address')
                                            ->columnSpanFull()
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('city')
                                            ->label('City')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('state')
                                            ->label('State')
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
                                            ]),
                                        Forms\Components\TextInput::make('zip_code')
                                            ->label('Zip Code')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('google_maps_url')
                                            ->label('Google Maps URL')
                                            ->prefixIcon('heroicon-o-map-pin')
                                            ->columnSpanFull()
                                            ->maxLength(255),
                                    ])
                                    ->columns(2),
                                Forms\Components\Section::make('Description')
                                    ->description('Additional information about the customer.')
                                    ->schema([
                                        Forms\Components\Textarea::make('description')
                                            ->label('Description')
                                            ->columnSpanFull()
                                            ->maxLength(255),
                                    ]),
                                Forms\Components\Section::make('Attachments')
                                    ->description('Upload additional documents and files related to the customer.')
                                    ->schema([
                                        Forms\Components\FileUpload::make('attachments')
                                            ->label('Additional Attachments')
                                            ->multiple()
                                            ->directory('customer-attachments')
                                            // ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                                            ->maxFiles(10)
                                            ->maxSize(5120) // 5MB
                                            ->downloadable()
                                            ->previewable()
                                            ->storeFileNamesIn('attachment_original_names')
                                            ->getUploadedFileNameForStorageUsing(function ($file) {
                                                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                                                $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
                                                $timestamp = now()->format('YmdHis');
                                                $randomString = \Illuminate\Support\Str::random(6);

                                                return "{$originalName}_{$timestamp}_{$randomString}.{$extension}";
                                            })
                                            ->columnSpanFull()
                                            ->helperText('Upload documents, images, or other files related to this customer. Max 10 files, 5MB each. Original filenames will be preserved with unique suffix.'),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Primary Information
                Tables\Columns\TextColumn::make('name')
                    ->label('Customer Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-user'),

                Tables\Columns\TextColumn::make('company')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-building-office')
                    ->weight('medium')
                    ->color('gray')
                    ->limit(30)
                    ->tooltip(fn($record) => $record->company),

                // Contact Information
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Email copied!')
                    ->icon('heroicon-o-envelope')
                    ->color('blue')
                    ->limit(25)
                    ->tooltip(fn($record) => $record->email),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Phone number copied!')
                    ->icon('heroicon-o-phone')
                    ->color('green')
                    ->placeholder('No phone'),

                // Location Information
                Tables\Columns\TextColumn::make('location')
                    ->label('Location')
                    ->state(function ($record) {
                        $location = collect([
                            $record->city,
                            $record->state,
                            $record->country?->name
                        ])->filter()->implode(', ');

                        return $location ?: 'Not specified';
                    })
                    ->icon('heroicon-o-map-pin')
                    ->color('slate')
                    ->limit(35)
                    ->tooltip(function ($record) {
                        return collect([
                            $record->address,
                            $record->city,
                            $record->state,
                            $record->country?->name,
                            $record->zip_code
                        ])->filter()->implode(', ');
                    }),

                // Business Information
                Tables\Columns\TextColumn::make('customerGroup.name')
                    ->label('Customer Group')
                    ->badge()
                    ->color(fn($record) => match ($record->customerGroup?->name) {
                        'VIP' => 'warning',
                        'Premium' => 'success',
                        'Standard' => 'info',
                        'Basic' => 'gray',
                        default => 'slate'
                    })
                    ->icon(fn($record) => match ($record->customerGroup?->name) {
                        'VIP' => 'heroicon-o-star',
                        'Premium' => 'heroicon-o-crown',
                        'Standard' => 'heroicon-o-user-group',
                        'Basic' => 'heroicon-o-user',
                        default => 'heroicon-o-tag'
                    })
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->placeholder('No group')
                    ->tooltip(fn($record) => $record->customerGroup?->name ? "Customer Group: {$record->customerGroup->name}" : 'No customer group assigned'),

                Tables\Columns\TextColumn::make('lead.name')
                    ->label('Lead Source')
                    ->badge()
                    ->color(fn($record) => match (true) {
                        str_contains(strtolower($record->lead?->name ?? ''), 'referral') => 'success',
                        str_contains(strtolower($record->lead?->name ?? ''), 'website') => 'info',
                        str_contains(strtolower($record->lead?->name ?? ''), 'social') => 'purple',
                        str_contains(strtolower($record->lead?->name ?? ''), 'email') => 'blue',
                        str_contains(strtolower($record->lead?->name ?? ''), 'phone') => 'green',
                        str_contains(strtolower($record->lead?->name ?? ''), 'advertising') => 'orange',
                        default => 'gray'
                    })
                    ->icon(fn($record) => match (true) {
                        str_contains(strtolower($record->lead?->name ?? ''), 'referral') => 'heroicon-o-user-group',
                        str_contains(strtolower($record->lead?->name ?? ''), 'website') => 'heroicon-o-globe-alt',
                        str_contains(strtolower($record->lead?->name ?? ''), 'social') => 'heroicon-o-hashtag',
                        str_contains(strtolower($record->lead?->name ?? ''), 'email') => 'heroicon-o-envelope',
                        str_contains(strtolower($record->lead?->name ?? ''), 'phone') => 'heroicon-o-phone',
                        str_contains(strtolower($record->lead?->name ?? ''), 'advertising') => 'heroicon-o-megaphone',
                        default => 'heroicon-o-arrow-right-on-rectangle'
                    })
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->placeholder('No lead')
                    ->tooltip(fn($record) => $record->lead?->name ? "Lead Source: {$record->lead->name}" : 'No lead source specified'),

                Tables\Columns\TextColumn::make('default_language')
                    ->label('Language')
                    ->badge()
                    ->color('amber')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('website')
                    ->label('Website')
                    ->searchable()
                    ->url(fn($record) => $record->website ? (str_starts_with($record->website, 'http') ? $record->website : 'https://' . $record->website) : null)
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-globe-alt')
                    ->color('cyan')
                    ->limit(20)
                    ->placeholder('No website')
                    ->toggleable(isToggledHiddenByDefault: true),

                // Status
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                //Job Status
                Tables\Columns\TextColumn::make('job_status')
                    ->label('Job Status')
                    ->badge()
                    ->placeholder('Not specified')
                    ->searchable(),

                // Created At
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Join Date')
                    ->dateTime('d M Y')
                    ->sortable(),

                // Additional Information
                Tables\Columns\TextColumn::make('description')
                    ->label('Notes')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn($record) => $record->description)
                    ->placeholder('No description')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('attachments')
                    ->label('Attachments')
                    ->state(function ($record) {
                        if (!$record->attachments || count($record->attachments) === 0) {
                            return 'No files';
                        }
                        return count($record->attachments) . ' file(s)';
                    })
                    ->badge()
                    ->color(fn($record) => $record->attachments && count($record->attachments) > 0 ? 'success' : 'gray')
                    ->icon(fn($record) => $record->attachments && count($record->attachments) > 0 ? 'heroicon-o-paper-clip' : 'heroicon-o-document')
                    ->tooltip(function ($record) {
                        if (!$record->attachments || count($record->attachments) === 0) {
                            return 'No attachments';
                        }

                        $fileNames = collect($record->attachments)->map(function ($file, $index) use ($record) {
                            // Gunakan nama asli jika tersedia, jika tidak gunakan nama file dari path
                            $originalNames = $record->attachment_original_names ?? [];
                            $filename = $originalNames[$index] ?? basename($file);
                            return '• ' . $filename;
                        })->implode("\n");

                        return count($record->attachments) . " attachment(s):\n" . $fileNames;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),

                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ])
                    ->placeholder('All Statuses'),

                Tables\Filters\SelectFilter::make('customer_group_id')
                    ->label('Customer Group')
                    ->relationship('customerGroup', 'name')
                    ->placeholder('All Groups')
                    ->multiple()
                    ->preload()
                    ->searchable(),

                Tables\Filters\SelectFilter::make('lead_id')
                    ->label('Lead Source')
                    ->relationship('lead', 'name')
                    ->placeholder('All Lead Sources')
                    ->multiple()
                    ->preload()
                    ->searchable(),

                Tables\Filters\SelectFilter::make('country_id')
                    ->label('Country')
                    ->relationship('country', 'name')
                    ->placeholder('All Countries')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('has_contact')
                    ->label('Has Contact Info')
                    ->query(fn(Builder $query): Builder => $query->whereNotNull('email')->orWhereNotNull('phone'))
                    ->toggle(),
            ], layout: FiltersLayout::AboveContent)
            // ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->persistFiltersInSession()
            ->defaultSort('name', 'asc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)

            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->visible(fn(): bool => auth()->user()->can('view_customer')),
                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil')
                        ->color('warning')
                        ->visible(fn($record): bool => auth()->user()->can('update_customer') && ! $record->trashed()),
                    Tables\Actions\DeleteAction::make()
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->visible(fn($record): bool => auth()->user()->can('delete_customer') && ! $record->trashed()),
                    Tables\Actions\RestoreAction::make()
                        ->icon('heroicon-o-arrow-path')
                        ->visible(fn($record): bool => auth()->user()->can('restore_customer') && $record->trashed()),
                    Tables\Actions\ForceDeleteAction::make()
                        ->icon('heroicon-o-x-circle')
                        ->visible(fn($record): bool => auth()->user()->can('force_delete_customer') && $record->trashed()),
                ])
                    ->label('Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button()
                    ->visible(
                        fn(): bool =>
                        auth()->user()->can('view_customer') ||
                            auth()->user()->can('update_customer') ||
                            auth()->user()->can('delete_customer')
                    ),
            ], position: ActionsPosition::BeforeCells)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn(): bool => auth()->user()->can('delete_any_customer')),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn(): bool => auth()->user()->can('force_delete_any_customer')),
                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn(): bool => auth()->user()->can('restore_any_customer')),
                ])
                    ->visible(
                        fn(): bool =>
                        auth()->user()->can('delete_any_customer') ||
                            auth()->user()->can('force_delete_any_customer') ||
                            auth()->user()->can('restore_any_customer')
                    ),
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
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
            'view' => Pages\ViewCustomerTabs::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
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
                                                    ->icon('heroicon-o-envelope')
                                                    ->placeholder('-'),

                                                TextEntry::make('phone')
                                                    ->label('Phone')
                                                    ->copyable()
                                                    ->icon('heroicon-o-phone')
                                                    ->placeholder('-'),

                                                TextEntry::make('company')
                                                    ->label('Company')
                                                    ->icon('heroicon-o-building-office')
                                                    ->placeholder('-'),
                                            ]),
                                    ]),

                                Section::make('Lead Information')
                                    ->schema([
                                        Grid::make([
                                            'default' => 1,
                                            'sm' => 2,
                                        ])
                                            ->schema([
                                                TextEntry::make('lead.status.name')
                                                    ->label('Status')
                                                    ->badge()
                                                    ->color(fn(?string $state): string => match ($state) {
                                                        'Hot' => 'danger',
                                                        'Warm' => 'warning',
                                                        'Cold' => 'info',
                                                        default => 'gray',
                                                    })
                                                    ->placeholder('No status'),

                                                TextEntry::make('lead.source.name')
                                                    ->label('Source')
                                                    ->badge()
                                                    ->color('primary')
                                                    ->placeholder('No source'),

                                                TextEntry::make('lead.assigned.name')
                                                    ->label('Assigned To')
                                                    ->placeholder('Not assigned'),

                                                TextEntry::make('lead.lead_value')
                                                    ->label('Lead Value')
                                                    ->money('IDR')
                                                    ->color('success')
                                                    ->placeholder('No value'),

                                                TextEntry::make('lead.date_contacted')
                                                    ->label('Date Contacted')
                                                    ->date()
                                                    ->placeholder('Never contacted'),

                                                TextEntry::make('lead.is_public')
                                                    ->label('Public')
                                                    ->formatStateUsing(fn(?bool $state): string => $state !== null ? ($state ? 'Yes' : 'No') : 'Not specified')
                                                    ->icon(fn(?bool $state): string => $state !== null ? ($state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle') : 'heroicon-o-question-mark-circle')
                                                    ->iconColor(fn(?bool $state): string => $state !== null ? ($state ? 'success' : 'danger') : 'gray'),
                                            ]),
                                    ])
                                    ->visible(fn($record) => $record->lead_id !== null),

                                Section::make('Additional Information')
                                    ->schema([
                                        Grid::make([
                                            'default' => 1,
                                            'sm' => 2,
                                        ])
                                            ->schema([
                                                TextEntry::make('website')
                                                    ->label('Website')
                                                    ->url(fn(?string $state): ?string => $state ? (str_starts_with($state, 'http') ? $state : 'https://' . $state) : null)
                                                    ->openUrlInNewTab()
                                                    ->placeholder('-'),

                                                TextEntry::make('position')
                                                    ->label('Position')
                                                    ->placeholder('-'),

                                                TextEntry::make('default_language')
                                                    ->label('Default Language')
                                                    ->placeholder('-'),
                                            ]),
                                    ]),

                                Section::make('Attachments')
                                    ->schema([
                                        TextEntry::make('attachments')
                                            ->label('Uploaded Files')
                                            ->state(function ($record) {
                                                if (!$record->attachments || count($record->attachments) === 0) {
                                                    return 'No attachments uploaded';
                                                }

                                                $files = collect($record->attachments)->map(function ($file, $index) use ($record) {
                                                    // Gunakan nama asli jika tersedia, jika tidak gunakan nama file dari path
                                                    $originalNames = $record->attachment_original_names ?? [];
                                                    $filename = $originalNames[$index] ?? basename($file);

                                                    // url encode
                                                    $downloadUrl = asset('storage/' . $file);
                                                    $encodedFilename = rawurlencode(basename($file));
                                                    $downloadUrl = asset('storage/' . dirname($file) . '/' . $encodedFilename);
                                                    return "<a href='" . $downloadUrl . "' target='_blank' class='inline-flex items-center px-2 py-1 mr-2 mb-2 text-xs font-medium text-blue-800 bg-blue-100 rounded-full hover:bg-blue-200 dark:bg-blue-900 dark:text-blue-300'>
                                                        <svg class='w-3 h-3 mr-1' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                                            <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'></path>
                                                        </svg>
                                                        {$filename}
                                                    </a>";
                                                })->implode('');

                                                return $files;
                                            })
                                            ->html()
                                            ->columnSpanFull(),
                                    ])
                                    ->visible(fn($record) => $record->attachments && count($record->attachments) > 0),
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
                                                    ->columnSpanFull()
                                                    ->placeholder('-'),

                                                TextEntry::make('city')
                                                    ->label('City')
                                                    ->placeholder('-'),

                                                TextEntry::make('state')
                                                    ->label('State')
                                                    ->placeholder('-'),

                                                TextEntry::make('zip_code')
                                                    ->label('ZIP Code')
                                                    ->placeholder('-'),

                                                TextEntry::make('country.name')
                                                    ->label('Country')
                                                    ->placeholder('-'),

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
}
