<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CashAdvanceResource\Pages;
use App\Filament\Resources\CashAdvanceResource\RelationManagers;
use App\Models\CashAdvance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CashAdvanceExport;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use App\Models\Employee;
use Filament\Support\Enums\FontWeight;
use Filament\Support\RawJs;
use Filament\Tables\Enums\FiltersLayout;

class CashAdvanceResource extends Resource
{
    protected static ?string $model = CashAdvance::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Cash Advances';
    protected static ?string $modelLabel = 'Cash Advance';
    protected static ?string $pluralModelLabel = 'Cash Advances';

    protected static ?string $navigationGroup = 'Financial';
    protected static ?int $navigationSort = 7;

    protected static ?string $recordTitleAttribute = 'description';

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
                Forms\Components\Section::make('Cash Advance Details')
                    ->description('Enter the basic information for this cash advance request')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('date')
                                    ->label('Date')
                                    ->required()
                                    ->default(now())
                                    ->maxDate(now())
                                    ->prefixIcon('heroicon-m-calendar')
                                    ->native(false)
                                    ->closeOnDateSelection()
                                    ->columnSpan(1),

                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'paid' => 'Paid',
                                        'approved' => 'Approved',
                                        'rejected' => 'Rejected'
                                    ])
                                    ->required()
                                    ->default('pending')
                                    ->prefixIcon('heroicon-m-flag')
                                    ->native(false)
                                    ->live()
                                    ->columnSpan(1),
                                Forms\Components\Select::make('category')
                                    ->label('Category')
                                    ->options([
                                        'kasbon' => 'Kasbon',
                                        'reimbursement' => 'Reimbursement',
                                    ])
                                    ->required()
                                    ->placeholder('Select Category'),
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->required()
                            ->maxLength(500)
                            ->rows(3)
                            ->columnSpanFull()
                            ->hint('Provide a clear description of what this cash advance is for'),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('amount')
                                    ->label('Jumlah')
                                    ->required()
                                    ->prefix('Rp')
                                    ->prefixIcon('heroicon-m-banknotes')
                                    ->helperText('Masukkan jumlah dalam Rupiah')
                                    ->placeholder('0')
                                    ->mask(RawJs::make(<<<'JS'
                                        $money($input, ',')
                                    JS))
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('reference')
                                    ->label('Reference Number')
                                    ->maxLength(255)
                                    ->placeholder('e.g., REF-2024-001')
                                    ->prefixIcon('heroicon-m-hashtag')
                                    ->columnSpan(1),
                            ]),
                    ]),


                Forms\Components\Section::make('Attachment')
                    ->description('Upload any relevant documents or receipts for this cash advance')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\FileUpload::make('attachment')
                            ->label('Attachment')
                            ->directory('cash-advances')
                            ->maxSize(5120) // 5MB
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->helperText('Upload files up to 5MB. Accepted types: images, PDF.')
                            ->columnSpanFull()
                            ->enableOpen()
                            ->enableDownload()
                            ->enableReordering()
                            ->multiple(false),
                    ]),

                Forms\Components\Section::make('Assignment & Approval')
                    ->description('Assign to employee and set approval details')
                    ->icon('heroicon-o-users')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('employee_id')
                                    ->label('Employee')
                                    ->relationship('employee', 'name')
                                    ->searchable(['name', 'employee_id'])
                                    ->preload()
                                    ->required()
                                    ->prefixIcon('heroicon-m-user')
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->name}")
                                    ->columnSpan(1),

                                Forms\Components\Select::make('user_id')
                                    ->label('Processed By')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->default(fn() => auth()->id())
                                    ->prefixIcon('heroicon-m-user-circle')
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\DatePicker::make('paid_at')
                            ->label('Payment Date')
                            ->visible(fn($get) => $get('status') === 'paid')
                            ->required(fn($get) => $get('status') === 'paid')
                            ->prefixIcon('heroicon-m-calendar-days')
                            ->native(false)
                            ->maxDate(now())
                            ->hint('When was this cash advance paid?'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable()
                    ->searchable()
                    ->icon('heroicon-m-calendar')
                    ->tooltip(function ($record): string {
                        return \Carbon\Carbon::parse($record->date)->diffForHumans();
                    }),

                Tables\Columns\TextColumn::make('employee.name')
                    ->label('Employee')
                    ->searchable(['name', 'employee_id'])
                    ->sortable()
                    ->weight(FontWeight::Medium)
                    ->icon('heroicon-m-user')
                    ->color('primary')
                    ->description(fn($record) => $record->employee?->employee_id ? "ID: {$record->employee->employee_id}" : null),

                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => ucfirst($state))
                    ->placeholder('No Data'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description / Remarks')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    })
                    ->wrap(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->numeric(decimalPlaces: 0, thousandsSeparator: '.', decimalSeparator: ',')
                    ->sortable()
                    ->prefix('Rp')
                    ->weight(FontWeight::Bold)
                    ->color('success')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn(string $state): string => match ($state) {
                        'pending' => 'heroicon-m-clock',
                        'paid' => 'heroicon-m-check-circle',
                        'approved' => 'heroicon-m-check-circle',
                        'rejected' => 'heroicon-m-x-circle',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Payment Date')
                    ->date('d M Y')
                    ->sortable()
                    ->placeholder('Not paid yet')
                    ->icon('heroicon-m-calendar-days')
                    ->color('info')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('reference')
                    ->label('Reference')
                    ->searchable()
                    ->placeholder('No reference')
                    ->icon('heroicon-m-hashtag')
                    ->color('gray')
                    ->copyable()
                    ->copyMessage('Reference copied!')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Processed By')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-user-circle')
                    ->color('secondary')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->since()
                    ->icon('heroicon-m-clock')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                    ])
                    ->default('pending'),

                Tables\Filters\SelectFilter::make('employee_id')
                    ->label('Employee')
                    ->relationship('employee', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\Filter::make('date_range')
                    ->label('Date Range')
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
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
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

                Tables\Filters\Filter::make('amount_range')
                    ->label('Amount Range')
                    ->form([
                        Forms\Components\TextInput::make('amount_from')
                            ->label('Amount From')
                            ->numeric()
                            ->prefix('IDR'),
                        Forms\Components\TextInput::make('amount_to')
                            ->label('Amount To')
                            ->numeric()
                            ->prefix('IDR'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['amount_from'],
                                fn(Builder $query, $amount): Builder => $query->where('amount', '>=', $amount),
                            )
                            ->when(
                                $data['amount_to'],
                                fn(Builder $query, $amount): Builder => $query->where('amount', '<=', $amount),
                            );
                    }),

                Tables\Filters\TrashedFilter::make(),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->color('warning')
                        ->icon('heroicon-m-pencil-square'),
                    Tables\Actions\Action::make('markAsPaid')
                        ->label('Mark as Paid')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->visible(fn($record) => $record->status === 'pending')
                        ->requiresConfirmation()
                        ->modalDescription('Are you sure you want to mark this cash advance as paid?')
                        ->form([
                            Forms\Components\DatePicker::make('paid_at')
                                ->label('Payment Date')
                                ->default(now())
                                ->required()
                                ->maxDate(now()),
                            Forms\Components\TextInput::make('reference')
                                ->label('Payment Reference')
                                ->placeholder('e.g., Transfer ID, Check Number'),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update([
                                'status' => 'paid',
                                'paid_at' => $data['paid_at'],
                                'reference' => $data['reference'] ?? $record->reference,
                            ]);
                        })
                        ->successNotificationTitle('Cash advance marked as paid successfully'),
                    Tables\Actions\DeleteAction::make()
                        ->icon('heroicon-m-trash'),
                ])
                    ->label('Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->icon('heroicon-m-trash'),
                    Tables\Actions\BulkAction::make('markSelectedAsPaid')
                        ->label('Mark as Paid')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalDescription('Mark selected pending cash advances as paid?')
                        ->form([
                            Forms\Components\DatePicker::make('paid_at')
                                ->label('Payment Date')
                                ->default(now())
                                ->required()
                                ->maxDate(now()),
                        ])
                        ->action(function ($records, array $data) {
                            $records->where('status', 'pending')->each(function ($record) use ($data) {
                                $record->update([
                                    'status' => 'paid',
                                    'paid_at' => $data['paid_at'],
                                ]);
                            });
                        })
                        ->successNotificationTitle('Selected cash advances marked as paid'),
                ]),
            ])
            ->defaultSort('date', 'desc')
            ->persistSortInSession()
            ->persistFiltersInSession()
            ->striped()
            ->paginated([10, 25, 50, 100]);
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
            'index' => Pages\ListCashAdvances::route('/'),
            'create' => Pages\CreateCashAdvance::route('/create'),
            'edit' => Pages\EditCashAdvance::route('/{record}/edit'),
        ];
    }
}
