<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccessRequestResource\Pages;
use App\Filament\Resources\AccessRequestResource\RelationManagers;
use App\Models\AccessRequest;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;

class AccessRequestResource extends Resource
{
    protected static ?string $model = AccessRequest::class;
    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationLabel = 'Access Requests';
    protected static ?string $modelLabel = 'Access Request';
    protected static ?string $pluralModelLabel = 'Access Requests';
    protected static ?string $navigationGroup = 'Human Resources';
    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Request Information')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('Employee')
                            ->relationship('employee', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\DatePicker::make('request_date')
                            ->label('Request Date')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->closeOnDateSelection(),

                        Forms\Components\Select::make('request_type')
                            ->label('Request Type')
                            ->options([
                                'holiday' => 'Holiday',
                                'non_workday' => 'Non Work Day',
                            ])
                            ->required(),

                        Forms\Components\Textarea::make('reason')
                            ->label('Reason')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                            ])
                            ->default('pending')
                            ->required(),

                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Admin Notes')
                            ->rows(2)
                            ->columnSpanFull()
                            ->helperText('Internal notes for admin use'),

                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->rows(2)
                            ->columnSpanFull()
                            ->visible(fn($get) => $get('status') === 'rejected'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Timestamps')
                    ->schema([
                        Forms\Components\DateTimePicker::make('requested_at')
                            ->label('Requested At')
                            ->disabled(),

                        Forms\Components\Select::make('reviewed_by')
                            ->label('Reviewed By')
                            ->relationship('reviewer', 'name')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('reviewed_at')
                            ->label('Reviewed At')
                            ->disabled(),
                    ])
                    ->columns(3)
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
                    ->sortable(),

                Tables\Columns\TextColumn::make('employee.nik')
                    ->label('NIK')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('request_date')
                    ->label('Request Date')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('request_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'holiday' => 'warning',
                        'non_workday' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'holiday' => 'Holiday',
                        'non_workday' => 'Non Work Day',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Reason')
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 30 ? $state : null;
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
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('requested_at')
                    ->label('Requested At')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('reviewer.name')
                    ->label('Reviewed By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('reviewed_at')
                    ->label('Reviewed At')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('requested_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('request_type')
                    ->label('Request Type')
                    ->options([
                        'holiday' => 'Holiday',
                        'non_workday' => 'Non Work Day',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('request_date')
                    ->form([
                        Forms\Components\DatePicker::make('from_date')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('to_date')
                            ->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from_date'],
                                fn(Builder $query, $date): Builder => $query->whereDate('request_date', '>=', $date),
                            )
                            ->when(
                                $data['to_date'],
                                fn(Builder $query, $date): Builder => $query->whereDate('request_date', '<=', $date),
                            );
                    }),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(AccessRequest $record): bool => $record->status === 'pending')
                    ->action(function (AccessRequest $record): void {
                        $record->update([
                            'status' => 'approved',
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Request Approved')
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->rows(3),
                    ])
                    ->visible(fn(AccessRequest $record): bool => $record->status === 'pending')
                    ->action(function (AccessRequest $record, array $data): void {
                        $record->update([
                            'status' => 'rejected',
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                            'rejection_reason' => $data['rejection_reason'],
                        ]);

                        Notification::make()
                            ->title('Request Rejected')
                            ->success()
                            ->send();
                    }),

                // Tables\Actions\ViewAction::make(),
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
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
            'index' => Pages\ListAccessRequests::route('/'),
            'create' => Pages\CreateAccessRequest::route('/create'),
            'edit' => Pages\EditAccessRequest::route('/{record}/edit'),
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
