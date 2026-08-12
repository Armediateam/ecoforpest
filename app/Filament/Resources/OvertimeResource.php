<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OvertimeResource\Pages;
use App\Filament\Resources\OvertimeResource\RelationManagers;
use App\Models\Overtime;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Colors\Color;
use Carbon\Carbon;

class OvertimeResource extends Resource
{
    protected static ?string $model = Overtime::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Overtime Requests';

    protected static ?string $modelLabel = 'Overtime Request';

    protected static ?string $pluralModelLabel = 'Overtime Requests';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::getModel()::where('status', 'pending')->count() > 0 ? 'warning' : null;
    }

    protected static function calculateDuration(callable $get, callable $set): void
    {
        $startTime = $get('start_time');
        $endTime = $get('end_time');

        if ($startTime && $endTime) {
            $start = Carbon::parse($startTime);
            $end = Carbon::parse($endTime);

            if ($end->lessThan($start)) {
                $end->addDay();
            }

            $durationHours = abs($start->diffInMinutes($end) / 60);
            $set('duration_hour', round($durationHours, 2));
        }
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Employee Information')
                    ->description('Select the employee for this overtime request')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('Employee')
                            ->relationship('employee', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Select the employee who will be working overtime'),
                    ])->columnSpan(2),

                Forms\Components\Section::make('Overtime Details')
                    ->description('Specify the overtime schedule and duration')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Forms\Components\DatePicker::make('date')
                            ->label('Overtime Date')
                            ->required()
                            ->columnSpanFull()
                            ->displayFormat('d/m/Y')
                            ->helperText('Date when the overtime will be performed'),
                        Forms\Components\TimePicker::make('start_time')
                            ->label('Start Time')
                            ->required()
                            ->native(false)
                            ->helperText('Overtime start time')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                static::calculateDuration($get, $set);
                            }),
                        Forms\Components\TimePicker::make('end_time')
                            ->label('End Time')
                            ->required()
                            ->native(false)
                            ->helperText('Overtime end time')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                static::calculateDuration($get, $set);
                            }),
                        Forms\Components\TextInput::make('duration_hour')
                            ->label('Duration (Hours)')
                            ->numeric()
                            ->step(0.5)
                            ->suffix('hours')
                            ->readOnly()
                            ->helperText('Calculated automatically based on start and end time'),
                    ])->columns(3)->columnSpan(3),

                Forms\Components\Section::make('Request Information')
                    ->description('Provide reason and request details')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                            ])
                            ->default('pending')
                            ->required()
                            ->helperText('Current status of the overtime request'),
                        Forms\Components\DatePicker::make('request_date')
                            ->label('Request Date')
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->helperText('Date when this request was submitted'),
                        Forms\Components\Textarea::make('reason')
                            ->label('Reason for Overtime')
                            ->required()
                            ->maxLength(500)
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('Explain why this overtime is necessary'),
                    ])->columns(2)->columnSpan(2),

                Forms\Components\Section::make('Approval Information')
                    ->description('Approval details (if applicable)')
                    ->icon('heroicon-o-check-badge')
                    ->schema([
                        Forms\Components\Select::make('approved_by')
                            ->label('Approved By')
                            ->relationship('approvedBy', 'name')
                            ->searchable()
                            ->preload()
                            ->default(auth()->user()->id)
                            ->helperText('Person who approved/will approve this request'),
                        Forms\Components\DateTimePicker::make('approved_at')
                            ->label('Approved At')
                            ->native(false)
                            ->displayFormat('d/m/Y H:i')
                            ->helperText('Date and time when the request was approved'),
                    ])->columns(2)->columnSpan(2),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium)
                    ->icon('heroicon-o-user'),
                Tables\Columns\TextColumn::make('date')
                    ->label('Overtime Date')
                    ->date('d M Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar-days'),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Start Time')
                    ->time('H:i')
                    ->icon('heroicon-o-clock'),
                Tables\Columns\TextColumn::make('end_time')
                    ->label('End Time')
                    ->time('H:i')
                    ->icon('heroicon-o-clock'),
                Tables\Columns\TextColumn::make('duration_hour')
                    ->label('Duration (Hours)')
                    ->numeric(decimalPlaces: 1)
                    ->sortable()
                    ->suffix(' hrs')
                    ->alignCenter()
                    ->icon('heroicon-o-clock'),
                Tables\Columns\TextColumn::make('reason')
                    ->label('Reason')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 30) {
                            return null;
                        }
                        return $state;
                    }),
                Tables\Columns\TextColumn::make('status')
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
                Tables\Columns\TextColumn::make('request_date')
                    ->label('Request Date')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable()
                    ->icon('heroicon-o-calendar'),
                Tables\Columns\TextColumn::make('approvedBy.name')
                    ->label('Approved By')
                    ->placeholder('Not yet approved')
                    ->toggleable()
                    ->icon('heroicon-o-check-badge'),
                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Approved At')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->placeholder('Not yet approved')
                    ->toggleable()
                    ->icon('heroicon-o-check-badge'),
            ])
            ->defaultSort('request_date', 'desc')
            ->striped()
            ->filters([
                Tables\Filters\SelectFilter::make('employee')
                    ->relationship('employee', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                Tables\Filters\Filter::make('overtime_date')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('date_to')
                            ->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['date_to'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['date_from'] ?? null) {
                            $indicators['date_from'] = 'From: ' . Carbon::parse($data['date_from'])->format('d M Y');
                        }
                        if ($data['date_to'] ?? null) {
                            $indicators['date_to'] = 'To: ' . Carbon::parse($data['date_to'])->format('d M Y');
                        }
                        return $indicators;
                    }),
                Tables\Filters\SelectFilter::make('approvedBy')
                    ->relationship('approvedBy', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                Tables\Filters\TrashedFilter::make(),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->color('info'),
                Tables\Actions\EditAction::make()
                    ->color('warning'),
                Tables\Actions\DeleteAction::make()
                    ->color('danger'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No overtime records found')
            ->emptyStateDescription('Get started by creating your first overtime record.')
            ->emptyStateIcon('heroicon-o-clock');
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
            'index' => Pages\ListOvertimes::route('/'),
            'create' => Pages\CreateOvertime::route('/create'),
            'edit' => Pages\EditOvertime::route('/{record}/edit'),
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
