<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveResource\Pages;
use App\Filament\Resources\LeaveResource\RelationManagers;
use App\Models\Leave;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Support\Enums\IconPosition;

class LeaveResource extends Resource
{
    protected static ?string $model = Leave::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Leave Requests';

    protected static ?string $pluralModelLabel = 'Leave Requests';

    protected static ?string $modelLabel = 'Leave Request';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() > 0 ? 'warning' : null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Leave Request Details')
                    ->description('Fill in the details for the leave request')
                    ->icon('heroicon-m-calendar-days')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('employee_id')
                                    ->label('Employee')
                                    ->relationship('employee', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->prefixIcon('heroicon-m-user')
                                    ->placeholder('Select an employee'),

                                Forms\Components\Select::make('leave_type')
                                    ->label('Leave Type')
                                    ->options([
                                        'Annual Leave' => 'Annual Leave',
                                        'Sick Leave' => 'Sick Leave',
                                        'Emergency Leave' => 'Emergency Leave',
                                        'Maternity Leave' => 'Maternity Leave',
                                        'Paternity Leave' => 'Paternity Leave',
                                    ])
                                    ->required()
                                    ->prefixIcon('heroicon-m-calendar')
                                    ->placeholder('Select leave type'),

                                Forms\Components\DatePicker::make('start_date')
                                    ->label('Start Date')
                                    ->required()
                                    ->prefixIcon('heroicon-m-play')
                                    ->displayFormat('d/m/Y')
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        if ($state && $get('end_date')) {
                                            // Auto calculate duration helper text
                                        }
                                    }),

                                Forms\Components\DatePicker::make('end_date')
                                    ->label('End Date')
                                    ->required()
                                    ->prefixIcon('heroicon-m-stop')
                                    ->displayFormat('d/m/Y')
                                    ->afterOrEqual('start_date'),

                                Forms\Components\DatePicker::make('request_date')
                                    ->label('Request Date')
                                    ->required()
                                    ->default(now())
                                    ->prefixIcon('heroicon-m-calendar')
                                    ->displayFormat('d/m/Y'),

                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'approved' => 'Approved',
                                        'rejected' => 'Rejected',
                                    ])
                                    ->default('pending')
                                    ->required()
                                    ->prefixIcon('heroicon-m-flag'),
                            ]),

                        Forms\Components\Textarea::make('reason')
                            ->label('Reason for Leave')
                            ->required()
                            ->rows(3)
                            ->maxLength(500)
                            ->placeholder('Please provide a detailed reason for your leave request...')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Approval Details')
                    ->description('Information about the approval process')
                    ->icon('heroicon-o-check-circle')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('approved_by')
                                    ->label('Approved By')
                                    ->relationship('approvedBy', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->default(auth()->user()->id)
                                    ->prefixIcon('heroicon-o-check-circle')
                                    ->placeholder('Select approver'),

                                Forms\Components\DateTimePicker::make('approved_at')
                                    ->label('Approved At')
                                    ->nullable()
                                    ->prefixIcon('heroicon-m-check')
                                    ->displayFormat('d/m/Y H:i'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(fn($record) => $record === null), // Collapsed for new records
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.name')
                    ->label('Employee')
                    ->icon('heroicon-m-user')
                    ->iconPosition(IconPosition::Before)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('leave_type')
                    ->label('Leave Type')
                    ->icon('heroicon-m-calendar-days')
                    ->iconPosition(IconPosition::Before)
                    ->badge()
                    ->color(fn(?string $state): string => match ($state) {
                        'Annual Leave' => 'success',
                        'Sick Leave' => 'warning',
                        'Emergency Leave' => 'danger',
                        'Maternity Leave' => 'info',
                        'Paternity Leave' => 'purple',
                        default => 'gray',
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Start Date')
                    ->icon('heroicon-m-play')
                    ->iconPosition(IconPosition::Before)
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('End Date')
                    ->icon('heroicon-m-stop')
                    ->iconPosition(IconPosition::Before)
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration')
                    ->label('Duration')
                    ->icon('heroicon-m-clock')
                    ->iconPosition(IconPosition::Before)
                    ->getStateUsing(function ($record) {
                        if ($record->start_date && $record->end_date) {
                            $start = \Carbon\Carbon::parse($record->start_date);
                            $end = \Carbon\Carbon::parse($record->end_date);
                            $days = $start->diffInDays($end) + 1;
                            return $days . ($days > 1 ? ' days' : ' day');
                        }
                        return '-';
                    })
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Reason')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(?string $state): string => match (strtolower($state)) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn(?string $state): string => match (strtolower($state)) {
                        'pending' => 'heroicon-m-clock',
                        'approved' => 'heroicon-m-check-circle',
                        'rejected' => 'heroicon-m-x-circle',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->formatStateUsing(fn(?string $state): string => ucfirst($state))
                    ->searchable(),

                Tables\Columns\TextColumn::make('request_date')
                    ->label('Request Date')
                    ->icon('heroicon-m-calendar')
                    ->iconPosition(IconPosition::Before)
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('approvedBy.name')
                    ->label('Approved By')
                    ->icon('heroicon-o-check-circle')
                    ->iconPosition(IconPosition::Before)
                    ->placeholder('Not approved yet')
                    ->toggleable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Approved At')
                    ->icon('heroicon-m-check')
                    ->iconPosition(IconPosition::Before)
                    ->date('d M Y H:i')
                    ->placeholder('Not approved yet')
                    ->toggleable()
                    ->sortable(),
            ])
            ->defaultSort('request_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('employee')
                    ->relationship('employee', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->indicator('Employee'),

                Tables\Filters\SelectFilter::make('leave_type')
                    ->options([
                        'Annual Leave' => 'Annual Leave',
                        'Sick Leave' => 'Sick Leave',
                        'Emergency Leave' => 'Emergency Leave',
                        'Maternity Leave' => 'Maternity Leave',
                        'Paternity Leave' => 'Paternity Leave',
                    ])
                    ->multiple()
                    ->indicator('Leave Type'),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_date'],
                                fn(Builder $query, $date): Builder => $query->where('start_date', '>=', $date),
                            )
                            ->when(
                                $data['end_date'],
                                fn(Builder $query, $date): Builder => $query->where('end_date', '<=', $date),
                            );
                    })
                    ->indicator('Date Range'),

                Tables\Filters\SelectFilter::make('approvedBy')
                    ->relationship('approvedBy', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->indicator('Approved By'),

                Tables\Filters\TrashedFilter::make(),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->slideOver(),

                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('Are you sure you want to approve this leave request?')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);
                    })
                    ->visible(fn($record) => $record->status === 'pending'),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Are you sure you want to reject this leave request?')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'rejected',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);
                    })
                    ->visible(fn($record) => $record->status === 'pending'),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve')
                        ->label('Approve Selected')
                        ->icon('heroicon-m-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalDescription('Are you sure you want to approve the selected leave requests?')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each(function ($record) {
                                if ($record->status === 'pending') {
                                    $record->update([
                                        'status' => 'approved',
                                        'approved_by' => auth()->id(),
                                        'approved_at' => now(),
                                    ]);
                                }
                            });
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('reject')
                        ->label('Reject Selected')
                        ->icon('heroicon-m-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalDescription('Are you sure you want to reject the selected leave requests?')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each(function ($record) {
                                if ($record->status === 'pending') {
                                    $record->update([
                                        'status' => 'rejected',
                                        'approved_by' => auth()->id(),
                                        'approved_at' => now(),
                                    ]);
                                }
                            });
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->icon('heroicon-m-plus'),
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
            'index' => Pages\ListLeaves::route('/'),
            'create' => Pages\CreateLeave::route('/create'),
            'view' => Pages\ViewLeave::route('/{record}'),
            'edit' => Pages\EditLeave::route('/{record}/edit'),
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
