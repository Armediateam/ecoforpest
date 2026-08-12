<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use App\Models\Employee;
use Filament\Tables\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Support\Facades\Hash;
use Filament\Support\RawJs;
use Filament\Tables\Actions\ForceDeleteAction;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Employee Information')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Personal Information')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Section::make()
                                            ->schema([
                                                Forms\Components\TextInput::make('nik')
                                                    ->label('NIK')
                                                    ->required()
                                                    ->maxLength(50)
                                                    ->unique(ignoreRecord: true),

                                                Forms\Components\TextInput::make('name')
                                                    ->label('Full Name')
                                                    ->required()
                                                    ->maxLength(100),


                                                Forms\Components\TextInput::make('email')
                                                    ->label('Email Address')
                                                    ->email()
                                                    ->unique(ignoreRecord: true)
                                                    ->maxLength(255)
                                                    ->prefixIcon('heroicon-m-envelope'),

                                                Forms\Components\TextInput::make('password')
                                                    ->label('Password')
                                                    ->hint('Untuk pengguna aplikasi mobile.')
                                                    ->password()
                                                    ->dehydrateStateUsing(fn($state) => filled($state) ? Hash::make($state) : null)
                                                    ->dehydrated(fn($state) => filled($state))
                                                    ->revealable()
                                                    ->maxLength(255),

                                                Forms\Components\Select::make('gender')
                                                    ->label('Gender')
                                                    ->options([
                                                        'male' => 'Male',
                                                        'female' => 'Female',
                                                    ])
                                                    ->required(),

                                                Forms\Components\DatePicker::make('birth_date')
                                                    ->label('Birth Date')
                                                    ->required()
                                                    ->maxDate(now()->subYears(18)),

                                                Forms\Components\TextInput::make('birth_place')
                                                    ->label('Birth Place')
                                                    ->maxLength(100),

                                                Forms\Components\Select::make('marital_status')
                                                    ->label('Marital Status')
                                                    ->options([
                                                        'single' => 'Single',
                                                        'married' => 'Married',
                                                        'divorced' => 'Divorced',
                                                        'widowed' => 'Widowed',
                                                    ]),

                                                Forms\Components\TextInput::make('phone')
                                                    ->label('Phone Number')
                                                    ->tel()
                                                    ->maxLength(20)
                                                    ->prefixIcon('heroicon-m-phone'),

                                                Forms\Components\Select::make('religion')
                                                    ->label('Religion')
                                                    ->options([
                                                        'islam' => 'Islam',
                                                        'kristen_protestant' => 'Kristen Protestant',
                                                        'kristen_katolik' => 'Kristen Katolik',
                                                        'hindu' => 'Hindu',
                                                        'buddha' => 'Buddha',
                                                        'konghucu' => 'Konghucu',
                                                        'lainnya' => 'Lainnya',
                                                    ]),
                                            ])->columns(2)->columnSpan(1),

                                        Forms\Components\Section::make()
                                            ->schema([
                                                Forms\Components\FileUpload::make('photo')
                                                    ->label('Photo')
                                                    ->image()
                                                    ->directory('employee-photos')
                                                    ->visibility('private')
                                                    ->imageResizeMode('cover')
                                                    ->imageCropAspectRatio('1:1')
                                                    ->imageResizeTargetWidth('300')
                                                    ->imageResizeTargetHeight('300')
                                                    ->columnSpanFull(),

                                                Forms\Components\Textarea::make('notes')
                                                    ->label('Personal Notes')
                                                    ->columnSpanFull(),
                                            ])->columnSpan(1),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Employment Information')
                            ->icon('heroicon-o-briefcase')
                            ->schema([
                                Forms\Components\Section::make()
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('position_id')
                                                    ->label('Position')
                                                    ->relationship('position', 'title')
                                                    ->required()
                                                    ->searchable()
                                                    ->preload()
                                                    ->disabled(fn($livewire) => $livewire instanceof \App\Filament\Resources\EmployeeResource\Pages\EditEmployee)
                                                    ->helperText(fn($livewire) => $livewire instanceof \App\Filament\Resources\EmployeeResource\Pages\EditEmployee
                                                        ? 'Position cannot be changed after creation. Use Career History to track position changes.'
                                                        : null)
                                                    ->prefixIcon('heroicon-m-identification'),

                                                Forms\Components\Select::make('shift_id')
                                                    ->label('Shift Override')
                                                    ->relationship('shift', 'name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder('Use position/department default')
                                                    ->helperText(function ($get) {
                                                        $positionId = $get('position_id');
                                                        if (!$positionId) {
                                                            return 'Select a position first to see default shift information.';
                                                        }

                                                        $position = \App\Models\Position::with(['defaultShift', 'department.defaultShift'])->find($positionId);
                                                        if (!$position) {
                                                            return 'Position not found.';
                                                        }

                                                        if ($position->default_shift_id) {
                                                            return "Position default: {$position->defaultShift->name}";
                                                        } elseif ($position->department && $position->department->default_shift_id) {
                                                            return "Department default: {$position->department->defaultShift->name}";
                                                        } else {
                                                            return 'No default shift configured for this position or department.';
                                                        }
                                                    })
                                                    ->prefixIcon('heroicon-m-clock'),

                                                Forms\Components\DatePicker::make('join_date')
                                                    ->label('Join Date')
                                                    ->required()
                                                    ->maxDate(now()),

                                                Forms\Components\TextInput::make('employment_status')
                                                    ->label('Employment Status')
                                                    ->disabled()
                                                    ->helperText('Auto-calculated from latest contract'),

                                                Forms\Components\Select::make('status')
                                                    ->label('Employee Status')
                                                    ->options([
                                                        'active' => 'Active',
                                                        'inactive' => 'Inactive',
                                                        'on_leave' => 'On Leave',
                                                        'terminated' => 'Terminated',
                                                    ])
                                                    ->required()
                                                    ->default('active')
                                                    ->prefixIcon('heroicon-m-user-circle'),

                                                Forms\Components\Select::make('status_flag')
                                                    ->label('Employee Performance Status')
                                                    ->options([
                                                        'good_standing' => 'Good Standing',
                                                        'under_review' => 'Under Review',
                                                        'at_risk' => 'At Risk',
                                                    ])
                                                    ->default('good_standing')
                                                    ->prefixIcon('heroicon-m-arrow-trending-up'),
                                            ]),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Government & Financial')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Section::make('Government Information')
                                            ->icon('heroicon-o-building-library')
                                            ->schema([
                                                Forms\Components\TextInput::make('nik')
                                                    ->label('NIK (ID Number)')
                                                    ->maxLength(20)
                                                    ->prefixIcon('heroicon-m-identification'),

                                                Forms\Components\TextInput::make('npwp')
                                                    ->label('NPWP (Tax ID)')
                                                    ->maxLength(20)
                                                    ->prefixIcon('heroicon-m-document-check'),

                                                Forms\Components\TextInput::make('bpjs_number')
                                                    ->label('BPJS Number')
                                                    ->maxLength(20)
                                                    ->prefixIcon('heroicon-m-shield-check'),
                                            ])->columnSpan(1),

                                        Forms\Components\Section::make('Bank Information')
                                            ->icon('heroicon-o-banknotes')
                                            ->schema([
                                                Forms\Components\TextInput::make('bank_name')
                                                    ->label('Bank Name')
                                                    ->maxLength(100)
                                                    ->prefixIcon('heroicon-m-building-office'),

                                                Forms\Components\TextInput::make('bank_account')
                                                    ->label('Bank Account Number')
                                                    ->maxLength(50)
                                                    ->prefixIcon('heroicon-m-credit-card'),

                                                Forms\Components\TextInput::make('bank_account_name')
                                                    ->label('Bank Account Name')
                                                    ->maxLength(100)
                                                    ->prefixIcon('heroicon-m-user'),
                                            ])->columnSpan(1),
                                    ]),
                            ]),
                        Forms\Components\Tabs\Tab::make('Salary Information')
                            ->icon('heroicon-o-document-currency-dollar')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Repeater::make('employee_income')
                                            ->label('Income')
                                            ->schema([
                                                Forms\Components\TextInput::make('name')
                                                    ->required()
                                                    ->readonly(fn($get) => in_array($get('name'), ['Gaji Pokok', 'Uang Makan', 'Uang Transport'])),
                                                Forms\Components\TextInput::make('nominal')
                                                    ->prefix('Rp.')
                                                    ->mask(RawJs::make('$money($input, \',\')'))
                                                    ->stripCharacters(['.', ','])
                                                    ->required()
                                                    ->integer(),
                                            ])
                                            ->defaultItems(3)
                                            ->default([
                                                ['name' => 'Gaji Pokok', 'nominal' => 0],
                                                ['name' => 'Uang Makan', 'nominal' => 0],
                                                ['name' => 'Uang Transport', 'nominal' => 0],
                                            ])
                                            ->afterStateHydrated(function ($state) {
                                                $requiredItems = ['Gaji Pokok', 'Uang Makan', 'Uang Transport'];
                                                $existingNames = array_column($state ?? [], 'name');
                                                foreach ($requiredItems as $item) {
                                                    if (!in_array($item, $existingNames)) {
                                                        $state[] = ['name' => $item, 'nominal' => 0];
                                                    }
                                                }
                                                return $state;
                                            })
                                            ->mutateDehydratedStateUsing(function ($state) {
                                                // Filter out items without name
                                                $state = array_filter($state, fn($item) => !empty($item['name'] ?? ''));
                                                $requiredItems = ['Gaji Pokok', 'Uang Makan', 'Uang Transport'];
                                                $existingNames = array_column($state, 'name');
                                                foreach ($requiredItems as $item) {
                                                    if (!in_array($item, $existingNames)) {
                                                        $state[] = ['name' => $item, 'nominal' => 0];
                                                    }
                                                }
                                                return $state;
                                            })
                                            ->columns(2),
                                        Forms\Components\Repeater::make('employee_expense')
                                            ->label('Outcome')
                                            ->schema([
                                                Forms\Components\TextInput::make('name')
                                                    ->required(),
                                                Forms\Components\TextInput::make('nominal')
                                                    ->prefix('Rp.')
                                                    ->mask(RawJs::make('$money($input, \',\')'))
                                                    ->stripCharacters(['.', ','])
                                                    ->required()
                                                    ->integer(),
                                            ])
                                            ->defaultItems(1)
                                            ->columns(2)
                                    ])
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->label('Photo')
                    ->circular()
                    ->defaultImageUrl(fn(Employee $record): string => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=7F9CF5&background=EBF4FF'),

                Tables\Columns\TextColumn::make('nik')
                    ->label('NIK')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('position.title')
                    ->label('Position')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('effective_shift')
                    ->label('Shift')
                    ->state(function (Employee $record): string {
                        $shift = $record->getEffectiveShift();
                        if (!$shift) return 'No Shift';

                        $source = '';
                        if ($record->shift_id) {
                            $source = ' (Override)';
                        } elseif ($record->position && $record->position->default_shift_id) {
                            $source = ' (Position)';
                        } elseif ($record->position && $record->position->department && $record->position->department->default_shift_id) {
                            $source = ' (Department)';
                        }

                        return $shift->name . $source;
                    })
                    ->badge()
                    ->color(function (Employee $record): string {
                        if ($record->shift_id) return 'warning'; // Override
                        if ($record->position && $record->position->default_shift_id) return 'info'; // Position
                        if ($record->position && $record->position->department && $record->position->department->default_shift_id) return 'success'; // Department
                        return 'gray'; // No shift
                    })
                    ->sortable(false),

                Tables\Columns\TextColumn::make('employment_status')
                    ->label('Employment Status')
                    ->formatStateUsing(fn(string $state): string => ucfirst($state))
                    ->sortable()
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'permanent' => 'success',
                        'contract' => 'info',
                        'probation' => 'warning',
                        'internship' => 'gray',
                        default => 'gray',
                    })
                    ->icon('heroicon-m-document-text'),

                Tables\Columns\TextColumn::make('join_date')
                    ->label('Join Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'gray',
                        'on_leave' => 'warning',
                        'terminated' => 'danger',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state))
                    ->sortable(),

                Tables\Columns\IconColumn::make('status_flag')
                    ->label('Performance Status')
                    ->icon(fn(string $state): string => match ($state) {
                        'at_risk' => 'heroicon-o-exclamation-circle',
                        'under_review' => 'heroicon-o-question-mark-circle',
                        'good_standing' => 'heroicon-o-check-circle',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'at_risk' => 'danger',
                        'under_review' => 'warning',
                        'good_standing' => 'success',
                    })
                    ->tooltip(fn(string $state): string => match ($state) {
                        'at_risk' => 'At Risk',
                        'under_review' => 'Under Review',
                        'good_standing' => 'Good Standing',
                    })
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('position_id')
                    ->relationship('position', 'title')
                    ->label('Position')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('employment_status')
                    ->label('Employment Status')
                    ->options([
                        'permanent' => 'Permanent',
                        'contract' => 'Contract',
                        'probation' => 'Probation',
                        'internship' => 'Internship',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'on_leave' => 'On Leave',
                        'terminated' => 'Terminated',
                    ]),

                Tables\Filters\Filter::make('join_date')
                    ->form([
                        // Grid::make()
                        //     ->schema([
                        Forms\Components\DatePicker::make('join_date_from')
                            ->label('Join Date From'),
                        Forms\Components\DatePicker::make('join_date_until')
                            ->label('Join Date Until'),
                        // ])
                    ])->columns(2)->columnSpan(2)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['join_date_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('join_date', '>=', $date),
                            )
                            ->when(
                                $data['join_date_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('join_date', '<=', $date),
                            );
                    }),

                Tables\Filters\SelectFilter::make('shift')
                    ->relationship('shift', 'name')
                    ->label('Shift Override')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TrashedFilter::make(),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(6)
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                ForceDeleteAction::make()
                    ->visible(fn($record) => $record && method_exists($record, 'trashed') && $record->trashed()),
                Tables\Actions\RestoreAction::make(),
            ])->filtersTriggerAction(
                fn(Action $action) => $action
                    ->button()
                    ->label('Filter'),
            )
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),

                    Tables\Actions\BulkAction::make('assignShift')
                        ->label('Assign Shift')
                        ->icon('heroicon-m-clock')
                        ->form([
                            Forms\Components\Select::make('shift_id')
                                ->label('Shift')
                                ->relationship('shift', 'name')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->placeholder('Select shift to assign'),
                        ])
                        ->action(function (array $data, $records) {
                            foreach ($records as $record) {
                                $record->update(['shift_id' => $data['shift_id']]);
                            }
                        })
                        ->requiresConfirmation()
                        ->modalDescription('This will override the current shift assignment for selected employees.')
                        ->successNotificationTitle('Shifts assigned successfully'),

                    Tables\Actions\BulkAction::make('removeShiftOverride')
                        ->label('Remove Shift Override')
                        ->icon('heroicon-m-x-mark')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['shift_id' => null]);
                            }
                        })
                        ->requiresConfirmation()
                        ->modalDescription('This will remove shift overrides and use position/department defaults.')
                        ->successNotificationTitle('Shift overrides removed successfully')
                        ->color('gray'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AddressesRelationManager::class,
            RelationManagers\ContractsRelationManager::class,
            RelationManagers\CareerHistoriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'view' => Pages\ViewEmployee::route('/{record}'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
