<?php

namespace App\Filament\Resources;

use App\Exports\AttendanceExport;
use App\Filament\Resources\AttendanceResource\Pages;
use App\Filament\Resources\AttendanceResource\RelationManagers;
use App\Models\Attendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Enums\FiltersLayout;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Attendance';
    protected static ?string $modelLabel = 'Attendance Record';
    protected static ?string $pluralModelLabel = 'Attendance Records';
    protected static ?string $navigationGroup = 'Human Resources';
    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereDate('date', today())->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $todayCount = static::getModel()::whereDate('date', today())->count();

        if ($todayCount > 50) {
            return 'success';
        } elseif ($todayCount > 20) {
            return 'warning';
        }

        return 'primary';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Basic Information Section
                Forms\Components\Section::make('Basic Information')
                    ->description('Employee and attendance date information')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('Employee')
                            ->relationship('employee', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(2),
                        Forms\Components\DatePicker::make('date')
                            ->label('Attendance Date')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->closeOnDateSelection()
                            ->columnSpan(1),
                    ])
                    ->columns(3)
                    ->collapsible(),

                // Time Tracking Section
                Forms\Components\Section::make('Time Tracking')
                    ->description('Clock in and clock out times with status')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('clock_in')
                                    ->label('Clock In Time')
                                    ->native(false)
                                    ->displayFormat('d/m/Y H:i')
                                    ->seconds(false)
                                    ->required(),
                                Forms\Components\DateTimePicker::make('clock_out')
                                    ->label('Clock Out Time')
                                    ->native(false)
                                    ->displayFormat('d/m/Y H:i')
                                    ->seconds(false)
                                    ->after('clock_in'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('clock_in_status')
                                    ->label('Clock In Status')
                                    ->required()
                                    ->options([
                                        'Hadir' => 'Present',
                                        'Terlambat' => 'Late',
                                        'Tidak Hadir' => 'Absent',
                                        'Libur' => 'Holiday',
                                        'Belum Mulai Shift' => 'Before Shift',
                                        'Belum Absen' => 'Not Clocked In',
                                    ])
                                    ->default('Hadir')
                                    ->selectablePlaceholder(false),
                                Forms\Components\Select::make('clock_out_status')
                                    ->label('Clock Out Status')
                                    ->required()
                                    ->options([
                                        'Early Clock Out' => 'Early Clock Out',
                                        'Sudah Clock Out' => 'Clocked Out',
                                        'Belum Clock Out' => 'Not Clocked Out',
                                        'Libur' => 'Holiday',
                                        'Tidak Hadir' => 'Absent',
                                        'Belum Mulai Shift' => 'Before Shift',
                                        'Belum Absen' => 'Not Clocked In',
                                    ])
                                    ->default('Belum Clock Out')
                                    ->selectablePlaceholder(false),
                            ]),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->placeholder('Add any notes about this attendance record...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                // Leave Information Section
                Forms\Components\Section::make('Leave Information')
                    ->description('Leave status and details')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        Forms\Components\Toggle::make('is_leave')
                            ->label('On Leave')
                            ->helperText('Mark if this employee is on leave')
                            ->live()
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('leave_type')
                                    ->label('Leave Type')
                                    ->options([
                                        'annual' => 'Annual Leave',
                                        'sick' => 'Sick Leave',
                                        'emergency' => 'Emergency Leave',
                                        'maternity' => 'Maternity Leave',
                                        'paternity' => 'Paternity Leave',
                                        'unpaid' => 'Unpaid Leave',
                                        'skorsing' => 'Skorsing',
                                        'other' => 'Other',
                                    ])
                                    ->visible(fn(Forms\Get $get): bool => $get('is_leave'))
                                    ->required(fn(Forms\Get $get): bool => $get('is_leave')),
                                Forms\Components\Textarea::make('leave_reason')
                                    ->label('Leave Reason')
                                    ->placeholder('Provide reason for leave...')
                                    ->visible(fn(Forms\Get $get): bool => $get('is_leave'))
                                    ->required(fn(Forms\Get $get): bool => $get('is_leave')),
                            ])
                            ->visible(fn(Forms\Get $get): bool => $get('is_leave')),
                    ])
                    ->collapsible(),

                // Evidence Section
                Forms\Components\Section::make('Evidence & Documentation')
                    ->description('Photos and supporting documents')
                    ->icon('heroicon-o-camera')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\FileUpload::make('image_clock_in')
                                    ->label('Clock In Photo')
                                    ->image()
                                    ->imageEditor()
                                    ->imageEditorAspectRatios([
                                        '4:3',
                                        '16:9',
                                    ])
                                    ->directory('attendance/clock-in')
                                    ->visibility('private')
                                    ->maxSize(2048)
                                    ->helperText('Upload photo taken during clock in'),
                                Forms\Components\FileUpload::make('image_clock_out')
                                    ->label('Clock Out Photo')
                                    ->image()
                                    ->imageEditor()
                                    ->imageEditorAspectRatios([
                                        '4:3',
                                        '16:9',
                                    ])
                                    ->directory('attendance/clock-out')
                                    ->visibility('private')
                                    ->maxSize(2048)
                                    ->helperText('Upload photo taken during clock out'),
                            ]),
                    ])
                    ->collapsible(),

                // Location Information Section
                Forms\Components\Section::make('Location Information')
                    ->description('GPS coordinates for clock in and clock out')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Forms\Components\Fieldset::make('Clock In Location')
                            ->schema([
                                Forms\Components\TextInput::make('coordinate_clock_in.latitude')
                                    ->label('Latitude')
                                    ->numeric()
                                    ->step(0.000001)
                                    ->minValue(-90)
                                    ->maxValue(90)
                                    ->disabled()
                                    ->placeholder('e.g., -6.200000'),
                                Forms\Components\TextInput::make('coordinate_clock_in.longitude')
                                    ->label('Longitude')
                                    ->numeric()
                                    ->step(0.000001)
                                    ->minValue(-180)
                                    ->maxValue(180)
                                    ->disabled()
                                    ->placeholder('e.g., 106.816666'),
                            ])
                            ->columns(2),
                        Forms\Components\Fieldset::make('Clock Out Location')
                            ->schema([
                                Forms\Components\TextInput::make('coordinate_clock_out.latitude')
                                    ->label('Latitude')
                                    ->numeric()
                                    ->step(0.000001)
                                    ->minValue(-90)
                                    ->maxValue(90)
                                    ->disabled()
                                    ->placeholder('e.g., -6.200000'),
                                Forms\Components\TextInput::make('coordinate_clock_out.longitude')
                                    ->label('Longitude')
                                    ->numeric()
                                    ->step(0.000001)
                                    ->minValue(-180)
                                    ->maxValue(180)
                                    ->disabled()
                                    ->placeholder('e.g., 106.816666'),
                            ])
                            ->columns(2),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->copyable()
                    ->tooltip('Click to copy employee name'),

                Tables\Columns\TextColumn::make('employee.nik')
                    ->label('NIK')
                    ->searchable()
                    ->toggleable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('employee.position.title')
                    ->label('Position')
                    ->searchable()
                    ->toggleable()
                    ->limit(20)
                    ->tooltip(fn(?string $state): ?string => $state),

                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('clock_in')
                    ->label('Clock In')
                    ->time('H:i')
                    ->sortable()
                    ->badge()
                    ->color(fn(?string $state): string => $state ? 'success' : 'gray')
                    ->formatStateUsing(fn(?string $state): string => $state ? \Carbon\Carbon::parse($state)->format('H:i') : 'Not Set'),

                Tables\Columns\TextColumn::make('clock_out')
                    ->label('Clock Out')
                    ->time('H:i')
                    ->sortable()
                    ->badge()
                    ->color(fn(?string $state): string => $state ? 'success' : 'warning')
                    ->formatStateUsing(fn(?string $state): string => $state ? \Carbon\Carbon::parse($state)->format('H:i') : 'Not Set'),

                Tables\Columns\ViewColumn::make('working_hours')
                    ->label('Working Hours')
                    ->view('filament.tables.columns.working-hours')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('clock_in_status')
                    ->label('Clock In Status')
                    ->badge()
                    ->color(fn(?string $state): string => match ($state) {
                        'Hadir' => 'success',
                        'Terlambat' => 'warning',
                        'Tidak Hadir' => 'danger',
                        'Libur' => 'info',
                        'Belum Mulai Shift' => 'gray',
                        'Belum Absen' => 'gray',
                        null => 'gray',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn(?string $state): string => match ($state) {
                        'Hadir' => 'Present',
                        'Terlambat' => 'Late',
                        'Tidak Hadir' => 'Absent',
                        'Libur' => 'Holiday',
                        'Belum Mulai Shift' => 'Before Shift',
                        'Belum Absen' => 'Not Clocked In',
                        null => 'Not Set',
                        default => $state ?? 'Unknown',
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('clock_out_status')
                    ->label('Clock Out Status')
                    ->badge()
                    ->color(fn(?string $state): string => match ($state) {
                        'Sudah Clock Out' => 'success',
                        'Early Clock Out' => 'warning',
                        'Belum Clock Out' => 'danger',
                        'Libur' => 'info',
                        'Tidak Hadir' => 'danger',
                        'Belum Mulai Shift' => 'gray',
                        'Belum Absen' => 'gray',
                        null => 'gray',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn(?string $state): string => match ($state) {
                        'Sudah Clock Out' => 'Clocked Out',
                        'Early Clock Out' => 'Early Out',
                        'Belum Clock Out' => 'Not Out',
                        'Libur' => 'Holiday',
                        'Tidak Hadir' => 'Absent',
                        'Belum Mulai Shift' => 'Before Shift',
                        'Belum Absen' => 'Not Clocked In',
                        null => 'Not Set',
                        default => $state ?? 'Unknown',
                    })
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_leave')
                    ->label('On Leave')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('warning')
                    ->falseColor('success'),

                Tables\Columns\TextColumn::make('leave_type')
                    ->label('Leave Type')
                    ->badge()
                    ->color('warning')
                    ->formatStateUsing(fn(?string $state): string => $state ? ucfirst(str_replace('_', ' ', $state)) : '-')
                    ->searchable()
                    ->toggleable()
                    ->visible(fn($record) => $record && !empty($record->is_leave)),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(30)
                    ->tooltip(fn(?string $state): ?string => $state)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\ViewColumn::make('images')
                    ->label('Photos')
                    ->view('filament.tables.columns.attendance-images')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('employee')
                    ->label('Employee')
                    ->relationship('employee', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\SelectFilter::make('employee_position')
                    ->label('Position')
                    ->relationship('employee.position', 'title')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\Filter::make('attendance_date')
                    ->label('Date Range')
                    ->form([
                        Forms\Components\DatePicker::make('from_date')
                            ->label('From Date')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                        Forms\Components\DatePicker::make('to_date')
                            ->label('To Date')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from_date'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['to_date'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['from_date'] ?? null) {
                            $indicators['from_date'] = 'From: ' . \Carbon\Carbon::parse($data['from_date'])->format('d M Y');
                        }

                        if ($data['to_date'] ?? null) {
                            $indicators['to_date'] = 'To: ' . \Carbon\Carbon::parse($data['to_date'])->format('d M Y');
                        }

                        return $indicators;
                    }),

                Tables\Filters\SelectFilter::make('clock_in_status')
                    ->label('Clock In Status')
                    ->options([
                        'Hadir' => 'Present',
                        'Terlambat' => 'Late',
                        'Tidak Hadir' => 'Absent',
                        'Libur' => 'Holiday',
                        'Belum Mulai Shift' => 'Before Shift',
                        'Belum Absen' => 'Not Clocked In',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('clock_out_status')
                    ->label('Clock Out Status')
                    ->options([
                        'Early Clock Out' => 'Early Clock Out',
                        'Sudah Clock Out' => 'Clocked Out',
                        'Belum Clock Out' => 'Not Clocked Out',
                        'Libur' => 'Holiday',
                        'Tidak Hadir' => 'Absent',
                        'Belum Mulai Shift' => 'Before Shift',
                        'Belum Absen' => 'Not Clocked In',
                    ])
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_leave')
                    ->label('On Leave')
                    ->trueLabel('On Leave')
                    ->falseLabel('Not On Leave')
                    ->native(false),

                Tables\Filters\SelectFilter::make('leave_type')
                    ->label('Leave Type')
                    ->options([
                        'annual' => 'Annual Leave',
                        'sick' => 'Sick Leave',
                        'emergency' => 'Emergency Leave',
                        'maternity' => 'Maternity Leave',
                        'paternity' => 'Paternity Leave',
                        'unpaid' => 'Unpaid Leave',
                        'skorsing' => 'Skorsing',
                        'other' => 'Other',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('has_photos')
                    ->label('With Photos')
                    ->query(fn(Builder $query): Builder => $query->where(function ($query) {
                        $query->whereNotNull('image_clock_in')
                            ->orWhereNotNull('image_clock_out');
                    }))
                    ->toggle(),

                Tables\Filters\TrashedFilter::make(),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('View')
                    ->icon('heroicon-o-eye'),
                Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->icon('heroicon-o-pencil'),
                Tables\Actions\DeleteAction::make()
                    ->label('Delete')
                    ->icon('heroicon-o-trash'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),

                    Tables\Actions\BulkAction::make('exportSelected')
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function ($records) {
                            // Export as xlsx
                            return Excel::download(
                                new AttendanceExport($records),
                                'Selected Data Attendance - '  . date('d-m-Y') . '.xlsx'
                            );
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('date', 'desc')
            ->recordUrl(null)
            ->poll('30s')
            ->deferLoading()
            ->striped();
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
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'view' => Pages\ViewAttendance::route('/{record}'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
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
