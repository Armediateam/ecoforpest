<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShiftResource\Pages;
use App\Filament\Resources\ShiftResource\RelationManagers;
use App\Models\Shift;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Clusters\Settings;
use App\Models\Employee;
use Filament\Infolists;
use Filament\Infolists\Infolist;


class ShiftResource extends Resource
{
    protected static ?string $model = Shift::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-right-start-on-rectangle';

    protected static ?string $navigationGroup = 'Human Resources';

    protected static ?string $cluster = Settings::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Shift Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Shift Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g. Morning Shift, Night Shift')
                            ->helperText('Descriptive name for this shift'),

                        // Forms\Components\Textarea::make('description')
                        //     ->label('Description')
                        //     ->rows(2)
                        //     ->maxLength(500)
                        //     ->placeholder('Brief description of this shift')
                        //     ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Work Schedule')
                    ->schema([
                        Forms\Components\Repeater::make('workhour')
                            ->label('Daily Schedule')
                            ->schema([
                                Forms\Components\Select::make('day')
                                    ->label('Day')
                                    ->options([
                                        'monday' => 'Monday',
                                        'tuesday' => 'Tuesday',
                                        'wednesday' => 'Wednesday',
                                        'thursday' => 'Thursday',
                                        'friday' => 'Friday',
                                        'saturday' => 'Saturday',
                                        'sunday' => 'Sunday',
                                    ])
                                    ->required()
                                    ->distinct(),

                                Forms\Components\TimePicker::make('start_time')
                                    ->label('Start Time')
                                    ->required()
                                    ->seconds(false),

                                Forms\Components\TimePicker::make('end_time')
                                    ->label('End Time')
                                    ->required()
                                    ->seconds(false),

                                Forms\Components\TimePicker::make('break_start')
                                    ->label('Break Start')
                                    ->seconds(false),

                                Forms\Components\TimePicker::make('break_end')
                                    ->label('Break End')
                                    ->seconds(false)
                                    ->after('break_start'),

                                Forms\Components\TextInput::make('working_hours')
                                    ->label('Working Hours')
                                    ->numeric()
                                    ->step(0.5)
                                    ->suffix('hours')
                                    ->helperText('Total working hours (excluding breaks)'),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->collapsible()
                            ->itemLabel(fn(array $state): ?string => $state['day'] ?? 'New Schedule')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Shift Name')
                    ->searchable()
                    ->sortable(),

                // Tables\Columns\TextColumn::make('description')
                //     ->label('Description')
                //     ->limit(50)
                //     ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                //         $state = $column->getState();
                //         if (strlen($state) <= 50) {
                //             return null;
                //         }
                //         return $state;
                //     })
                //     ->toggleable(),

                Tables\Columns\TextColumn::make('schedule_summary')
                    ->label('Schedule')
                    ->state(function (Shift $record): string {
                        $workhours = $record->workhour;
                        if (!is_array($workhours) || empty($workhours)) {
                            return 'No schedule';
                        }

                        $days = count($workhours);
                        $firstDay = $workhours[0] ?? [];
                        $startTime = $firstDay['start_time'] ?? 'N/A';
                        $endTime = $firstDay['end_time'] ?? 'N/A';

                        return "{$days} days • {$startTime} - {$endTime}";
                    })
                    ->badge()
                    ->color('info'),

                // Tables\Columns\TextColumn::make('employees_count')
                //     ->label('Employees (Override)')
                //     ->counts('employees')
                //     ->badge()
                //     ->color('warning')
                //     ->tooltip('Employees with this shift as override'),

                // Tables\Columns\TextColumn::make('positions_count')
                //     ->label('Positions (Default)')
                //     ->counts('defaultForPositions')
                //     ->badge()
                //     ->color('info')
                //     ->tooltip('Positions using this as default shift'),

                // Tables\Columns\TextColumn::make('departments_count')
                //     ->label('Departments (Default)')
                //     ->counts('defaultForDepartments')
                //     ->badge()
                //     ->color('success')
                //     ->tooltip('Departments using this as default shift'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                // duplicate action
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->action(function (Shift $record) {
                        $newShift = $record->replicate();
                        $newShift->name = 'Copy of ' . $record->name;
                        $newShift->workhour = $record->workhour; // Copy the workhour array directly
                        $newShift->save();

                        // Redirect to the new shift's edit page
                        return redirect()->route('filament.resources.shifts.edit', ['record' => $newShift]);
                    })
                    ->requiresConfirmation()
                    ->modalDescription('Are you sure you want to duplicate this shift?'),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalDescription('Are you sure you want to delete this shift? This action cannot be undone and may affect employees, positions, and departments using this shift.'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Shift Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('Shift Name'),

                        // Infolists\Components\TextEntry::make('description')
                        //     ->label('Description')
                        //     ->placeholder('No description provided'),
                    ])->columns(2),

                Infolists\Components\Section::make('Work Schedule')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('workhour')
                            ->label('Daily Schedule')
                            ->schema([
                                Infolists\Components\TextEntry::make('day')
                                    ->label('Day')
                                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),

                                Infolists\Components\TextEntry::make('start_time')
                                    ->label('Start Time'),

                                Infolists\Components\TextEntry::make('end_time')
                                    ->label('End Time'),

                                Infolists\Components\TextEntry::make('break_start')
                                    ->label('Break Start')
                                    ->placeholder('No break'),

                                Infolists\Components\TextEntry::make('break_end')
                                    ->label('Break End')
                                    ->placeholder('No break'),

                                Infolists\Components\TextEntry::make('working_hours')
                                    ->label('Working Hours')
                                    ->suffix(' hours'),
                            ])
                            ->columns(3)
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Usage Statistics')
                    ->schema([
                        Infolists\Components\TextEntry::make('employees_count')
                            ->label('Employees (Override)')
                            ->state(fn(Shift $record): int => $record->employees()->count())
                            ->badge()
                            ->color('warning'),

                        Infolists\Components\TextEntry::make('positions_count')
                            ->label('Positions (Default)')
                            ->state(fn(Shift $record): int => $record->defaultForPositions()->count())
                            ->badge()
                            ->color('info'),

                        Infolists\Components\TextEntry::make('departments_count')
                            ->label('Departments (Default)')
                            ->state(fn(Shift $record): int => $record->defaultForDepartments()->count())
                            ->badge()
                            ->color('success'),

                        Infolists\Components\TextEntry::make('total_affected_employees')
                            ->label('Total Affected Employees')
                            ->state(function (Shift $record): int {
                                // Direct employees + employees via positions + employees via departments
                                $directEmployees = $record->employees()->count();

                                $positionEmployees = \App\Models\Employee::whereNull('shift_id')
                                    ->whereHas('position', function ($query) use ($record) {
                                        $query->where('default_shift_id', $record->id);
                                    })->count();

                                $departmentEmployees = \App\Models\Employee::whereNull('shift_id')
                                    ->whereHas('position', function ($query) use ($record) {
                                        $query->whereNull('default_shift_id')
                                            ->whereHas('department', function ($subQuery) use ($record) {
                                                $subQuery->where('default_shift_id', $record->id);
                                            });
                                    })->count();

                                return $directEmployees + $positionEmployees + $departmentEmployees;
                            })
                            ->badge()
                            ->color('primary'),
                    ])->columns(2),

                Infolists\Components\Section::make('Metadata')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created On')
                            ->dateTime(),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime(),
                    ])->columns(2)
                    ->collapsed(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\EmployeesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShifts::route('/'),
            'create' => Pages\CreateShift::route('/create'),
            'view' => Pages\ViewShift::route('/{record}'),
            'edit' => Pages\EditShift::route('/{record}/edit'),
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
