<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PermitResource\Pages;
use App\Filament\Resources\PermitResource\RelationManagers;
use App\Models\Permit;
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

class PermitResource extends Resource
{
    protected static ?string $model = Permit::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'HR Management';

    protected static ?string $navigationLabel = 'Permits Requests';

    protected static ?string $pluralModelLabel = 'Permits Requests';

    protected static ?string $modelLabel = 'Permits Request';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'employee.name';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::getModel()::where('status', 'pending')->count() > 0 ? 'warning' : 'success';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Employee & Date Information')
                    ->description('Select the employee and specify the permit date and time')
                    ->icon('heroicon-o-user-circle')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('Select Employee')
                            ->relationship('employee', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->placeholder('Search and select an employee')
                            ->helperText('Start typing to search for an employee'),
                        Forms\Components\DatePicker::make('date')
                            ->label('Permit Date')
                            ->required()
                            ->displayFormat('d/m/Y'),
                        Forms\Components\TimePicker::make('start_time')
                            ->label('Start Time')
                            ->required()
                            ->seconds(false),
                        Forms\Components\TimePicker::make('end_time')
                            ->label('End Time')
                            ->required()
                            ->seconds(false)
                            ->after('start_time'),
                    ])->columns(2),

                Forms\Components\Section::make('Request Details')
                    ->description('Provide reason and additional information')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\Textarea::make('reason')
                            ->label('Reason for Permit')
                            ->placeholder('Please provide a detailed reason for this permit request...')
                            ->rows(4)
                            ->columnSpanFull()
                            ->required()
                            ->maxLength(500)
                            ->helperText('Maximum 500 characters'),
                        Forms\Components\DatePicker::make('request_date')
                            ->label('Request Date')
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->disabled(),
                    ])->columns(1),

                Forms\Components\Section::make('Status & Approval')
                    ->description('Status and approval information')
                    ->icon('heroicon-o-check-circle')
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
                            ->native(false),
                        Forms\Components\Select::make('approved_by')
                            ->label('Approved By')
                            ->relationship('approvedBy', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Select approver')
                            ->helperText('Leave empty if not yet approved'),
                        Forms\Components\DateTimePicker::make('approved_at')
                            ->label('Approved At')
                            ->native(false)
                            ->displayFormat('d/m/Y H:i')
                            ->seconds(false)
                            ->helperText('Will be set automatically when approved'),
                    ])->columns(2)
                    ->collapsible()
                    ->collapsed(fn(?string $operation): bool => $operation === 'create'),
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
                    ->weight(FontWeight::Medium),
                Tables\Columns\TextColumn::make('date')
                    ->label('Permit Date')
                    ->date('d M Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar-days'),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Start Time')
                    ->icon('heroicon-o-clock'),
                Tables\Columns\TextColumn::make('end_time')
                    ->label('End Time')
                    ->icon('heroicon-o-clock'),
                Tables\Columns\TextColumn::make('reason')
                    ->label('Reason')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
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
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('approvedBy.name')
                    ->label('Approved By')
                    ->placeholder('Not assigned')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Approved At')
                    ->date('d M Y H:i')
                    ->sortable()
                    ->placeholder('Not approved yet')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('request_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('employee')
                    ->relationship('employee', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('permit_date')
                    ->form([
                        Forms\Components\DatePicker::make('date')
                            ->label('Request Permits Date')
                            ->displayFormat('d-m-Y'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date'],
                                fn(Builder $query, $date): Builder => $query->whereDate('request_date', $date),
                            );
                    }),
                Tables\Filters\SelectFilter::make('approvedBy')
                    ->relationship('approvedBy', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TrashedFilter::make(),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->color('info'),
                Tables\Actions\EditAction::make()
                    ->color('warning'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No permits found')
            ->emptyStateDescription('There are no permits matching your criteria.')
            ->emptyStateIcon('heroicon-o-document-text');
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
            'index' => Pages\ListPermits::route('/'),
            'create' => Pages\CreatePermit::route('/create'),
            'view' => Pages\ViewPermit::route('/{record}'),
            'edit' => Pages\EditPermit::route('/{record}/edit'),
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
