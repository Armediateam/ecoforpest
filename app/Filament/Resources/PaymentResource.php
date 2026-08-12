<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Resources\PaymentResource\RelationManagers;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Support\Colors\Color;
use Filament\Infolists\Infolist;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Payments';

    protected static ?string $modelLabel = 'Payment';

    protected static ?string $pluralModelLabel = 'Payments';

    protected static ?string $navigationGroup = 'Financial';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereDate('payment_date', today())->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $todayCount = static::getModel()::whereDate('payment_date', today())->count();

        if ($todayCount > 10) {
            return 'success';
        } elseif ($todayCount > 5) {
            return 'warning';
        }

        return 'primary';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Payment Information Section
                Forms\Components\Section::make('Payment Information')
                    ->description('Basic payment details and transaction information')
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        Forms\Components\Select::make('invoice_id')
                            ->label('Invoice')
                            ->relationship('invoice', 'invoice_number')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, ?string $state) {
                                if ($state) {
                                    $invoice = \App\Models\Invoice::find($state);
                                    if ($invoice) {
                                        $set('amount', $invoice->total);
                                    }
                                }
                            })
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('amount')
                            ->label('Payment Amount')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->step(0.01)
                            ->minValue(0)
                            ->placeholder('0.00')
                            ->columnSpan(1),
                    ])
                    ->columns(3)
                    ->collapsible(),

                // Payment Method & Date Section
                Forms\Components\Section::make('Payment Details')
                    ->description('Payment method, date, and transaction details')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('payment_mode')
                                    ->label('Payment Method')
                                    ->required()
                                    ->options(function () {
                                        $banksSetting = \App\Models\Setting::where('key', 'banks')->first();

                                        if ($banksSetting && $banksSetting->value) {
                                            $banksData = is_array($banksSetting->value) ? $banksSetting->value : json_decode($banksSetting->value, true);

                                            if (is_array($banksData)) {
                                                $options = [];
                                                foreach ($banksData as $key => $value) {
                                                    // Create display names for payment methods
                                                    if ($key === 'Tunai') {
                                                        $options[$key] = 'Cash Payment';
                                                    } else {
                                                        $options[$key] = $key . ' - Transfer';
                                                    }
                                                }
                                                return $options;
                                            }
                                        }

                                        // Fallback to default values if setting not found
                                        return [
                                            'Tunai' => 'Tunai',
                                        ];
                                    })
                                    ->native(false)
                                    ->searchable(),

                                Forms\Components\DatePicker::make('payment_date')
                                    ->label('Payment Date')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->default(now())
                                    ->closeOnDateSelection(),
                            ]),

                        Forms\Components\TextInput::make('transaction_id')
                            ->label('Transaction ID')
                            ->placeholder('Enter transaction ID or reference number')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('note')
                            ->label('Notes')
                            ->placeholder('Add any notes about this payment...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                // Receipt Upload Section
                Forms\Components\Section::make('Receipt & Documentation')
                    ->description('Upload payment receipt and supporting documents')
                    ->icon('heroicon-o-document-arrow-up')
                    ->schema([
                        Forms\Components\FileUpload::make('receipt')
                            ->label('Payment Receipt')
                            ->image()
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '4:3',
                                '16:9',
                            ])
                            ->directory('payments/receipts')
                            ->visibility('private')
                            ->maxSize(5120) // 5MB
                            ->helperText('Upload payment receipt (Image or PDF, max 5MB)')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Payment ID')
                    ->formatStateUsing(fn($state) => '#' . str_pad($state, 4, '0', STR_PAD_LEFT))
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon('heroicon-m-hashtag')
                    ->copyable()
                    ->tooltip('Click to copy Payment ID'),

                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('Invoice')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon('heroicon-m-document-text')
                    ->color('primary')
                    ->tooltip('Click to view invoice')
                    ->copyable(),

                Tables\Columns\TextColumn::make('invoice.customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon('heroicon-m-user')
                    ->limit(25)
                    ->tooltip(fn(?string $state): ?string => $state)
                    ->copyable(),

                Tables\Columns\TextColumn::make('payment_mode')
                    ->label('Payment Method')
                    ->searchable()
                    ->sortable()
                    ->badge(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('IDR')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('success')
                    ->icon('heroicon-m-banknotes')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Payment Date')
                    ->date('d M Y')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-calendar-days')
                    ->color(function ($record): string {
                        $paymentDate = \Carbon\Carbon::parse($record->payment_date);
                        $today = \Carbon\Carbon::today();

                        if ($paymentDate->isToday()) {
                            return 'success';
                        } elseif ($paymentDate->isYesterday()) {
                            return 'warning';
                        } elseif ($paymentDate->diffInDays($today) <= 7) {
                            return 'info';
                        }

                        return 'gray';
                    })
                    ->description(function ($record): string {
                        return \Carbon\Carbon::parse($record->payment_date)->diffForHumans();
                    }),

                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('Transaction ID')
                    ->searchable()
                    ->badge()
                    ->color('gray')
                    ->icon('heroicon-m-identification')
                    ->placeholder('N/A')
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('receipt')
                    ->label('Receipt')
                    ->boolean()
                    ->trueIcon('heroicon-o-document-check')
                    ->falseIcon('heroicon-o-document-minus')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->tooltip(function ($record): string {
                        return $record->receipt ? 'Receipt uploaded' : 'No receipt uploaded';
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('note')
                    ->label('Notes')
                    ->limit(30)
                    ->tooltip(fn(?string $state): ?string => $state)
                    ->placeholder('No notes')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->color('gray')
                    ->icon('heroicon-m-clock')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('payment_date_range')
                    ->label('Payment Date Range')
                    ->form([
                        Forms\Components\DatePicker::make('payment_from')
                            ->label('From Date')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->placeholder('Select start date'),
                        Forms\Components\DatePicker::make('payment_until')
                            ->label('Until Date')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->placeholder('Select end date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['payment_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('payment_date', '>=', $date),
                            )
                            ->when(
                                $data['payment_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('payment_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['payment_from'] ?? null) {
                            $indicators['payment_from'] = 'From: ' . \Carbon\Carbon::parse($data['payment_from'])->format('d M Y');
                        }

                        if ($data['payment_until'] ?? null) {
                            $indicators['payment_until'] = 'Until: ' . \Carbon\Carbon::parse($data['payment_until'])->format('d M Y');
                        }

                        return $indicators;
                    }),

                Tables\Filters\SelectFilter::make('payment_mode')
                    ->label('Payment Method')
                    ->options(function () {
                        $banksSetting = \App\Models\Setting::where('key', 'banks')->first();

                        if ($banksSetting && $banksSetting->value) {
                            $banksData = is_array($banksSetting->value) ? $banksSetting->value : json_decode($banksSetting->value, true);

                            if (is_array($banksData)) {
                                $options = [];
                                foreach ($banksData as $key => $value) {
                                    $options[$key] = $key;
                                }
                                return $options;
                            }
                        }

                        // Fallback to default values if setting not found
                        return [
                            'Tunai' => 'Tunai',
                        ];
                    })
                    ->multiple()
                    ->searchable(),

                Tables\Filters\Filter::make('amount_range')
                    ->label('Amount Range')
                    ->form([
                        Forms\Components\TextInput::make('amount_min')
                            ->label('Minimum Amount')
                            ->numeric()
                            ->prefix('Rp')
                            ->placeholder('0'),
                        Forms\Components\TextInput::make('amount_max')
                            ->label('Maximum Amount')
                            ->numeric()
                            ->prefix('Rp')
                            ->placeholder('999,999,999'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['amount_min'],
                                fn(Builder $query, $amount): Builder => $query->where('amount', '>=', $amount),
                            )
                            ->when(
                                $data['amount_max'],
                                fn(Builder $query, $amount): Builder => $query->where('amount', '<=', $amount),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['amount_min'] ?? null) {
                            $indicators['amount_min'] = 'Min: Rp ' . number_format($data['amount_min'], 0, ',', '.');
                        }

                        if ($data['amount_max'] ?? null) {
                            $indicators['amount_max'] = 'Max: Rp ' . number_format($data['amount_max'], 0, ',', '.');
                        }

                        return $indicators;
                    }),

                Tables\Filters\SelectFilter::make('customer')
                    ->label('Customer')
                    ->relationship('invoice.customer', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\SelectFilter::make('invoice_status')
                    ->label('Invoice Status')
                    ->relationship('invoice', 'status')
                    ->options([
                        'Draft' => 'Draft',
                        'Unpaid' => 'Unpaid',
                        'Paid' => 'Paid',
                        'Overdue' => 'Overdue',
                    ])
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('has_receipt')
                    ->label('Has Receipt')
                    ->trueLabel('With Receipt')
                    ->falseLabel('Without Receipt')
                    ->queries(
                        true: fn(Builder $query) => $query->whereNotNull('receipt'),
                        false: fn(Builder $query) => $query->whereNull('receipt'),
                    )
                    ->native(false),

                Tables\Filters\TernaryFilter::make('has_transaction_id')
                    ->label('Has Transaction ID')
                    ->trueLabel('With Transaction ID')
                    ->falseLabel('Without Transaction ID')
                    ->queries(
                        true: fn(Builder $query) => $query->whereNotNull('transaction_id'),
                        false: fn(Builder $query) => $query->whereNull('transaction_id'),
                    )
                    ->native(false),

                Tables\Filters\Filter::make('recent_payments')
                    ->label('Recent Payments')
                    ->query(fn(Builder $query): Builder => $query->whereDate('payment_date', '>=', now()->subDays(7)))
                    ->toggle(),

                Tables\Filters\Filter::make('today_payments')
                    ->label('Today\'s Payments')
                    ->query(fn(Builder $query): Builder => $query->whereDate('payment_date', today()))
                    ->toggle(),

            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('View Payment')
                        ->icon('heroicon-o-eye')
                        ->color('info'),

                    Tables\Actions\Action::make('view_receipt')
                        ->label('View Receipt')
                        ->icon('heroicon-o-document-magnifying-glass')
                        ->color('success')
                        ->url(fn($record) => $record->receipt ? asset('storage/' . $record->receipt) : null)
                        ->openUrlInNewTab()
                        ->visible(fn($record) => !empty($record->receipt)),

                    Tables\Actions\Action::make('view_invoice')
                        ->label('View Invoice')
                        ->icon('heroicon-o-document-text')
                        ->color('primary')
                        ->visible(fn($record) => !empty($record->invoice)),

                    Tables\Actions\EditAction::make()
                        ->label('Edit Payment')
                        ->icon('heroicon-o-pencil-square')
                        ->color('warning'),

                    Tables\Actions\DeleteAction::make()
                        ->label('Delete Payment')
                        ->icon('heroicon-o-trash')
                        ->color('danger'),
                ])
                    ->label('Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Delete Selected')
                        ->icon('heroicon-o-trash'),

                    Tables\Actions\BulkAction::make('export_payments')
                        ->label('Export Payments')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(function ($records) {
                            return response()->streamDownload(function () use ($records) {
                                echo "Payment ID,Invoice Number,Customer,Payment Method,Amount,Payment Date,Transaction ID,Notes\n";
                                foreach ($records as $record) {
                                    echo sprintf(
                                        "%s,%s,%s,%s,%s,%s,%s,%s\n",
                                        '#' . str_pad($record->id, 4, '0', STR_PAD_LEFT),
                                        $record->invoice->invoice_number ?? 'N/A',
                                        $record->invoice->customer->name ?? 'N/A',
                                        $record->payment_mode ?? 'N/A',
                                        'Rp ' . number_format($record->amount, 0, ',', '.'),
                                        $record->payment_date,
                                        $record->transaction_id ?? 'N/A',
                                        str_replace(["\r", "\n", ","], [" ", " ", ";"], $record->note ?? 'N/A')
                                    );
                                }
                            }, 'payments-export-' . now()->format('Y-m-d') . '.csv');
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Export Selected Payments')
                        ->modalDescription('Are you sure you want to export the selected payment records?')
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('mark_verified')
                        ->label('Mark as Verified')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                // Add verification logic here if needed
                                // For now, we'll just add a note
                                $record->update([
                                    'note' => ($record->note ?? '') . (empty($record->note) ? '' : ' | ') . 'Verified on ' . now()->format('Y-m-d H:i')
                                ]);
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Mark Payments as Verified')
                        ->modalDescription('This will add a verification note to the selected payments.')
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('payment_date', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->extremePaginationLinks()
            ->searchOnBlur()
            ->deferLoading()
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession()
            ->poll('60s');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Payment Overview Section
                \Filament\Infolists\Components\Section::make('Payment Overview')
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        \Filament\Infolists\Components\Grid::make(3)->schema([
                            \Filament\Infolists\Components\TextEntry::make('id')
                                ->label('Payment ID')
                                ->formatStateUsing(fn($state) => '#' . str_pad($state, 4, '0', STR_PAD_LEFT))
                                ->copyable(),
                            \Filament\Infolists\Components\TextEntry::make('amount')
                                ->label('Payment Amount')
                                ->money('IDR')
                                ->size('lg')
                                ->weight('bold')
                                ->color('success'),
                            \Filament\Infolists\Components\TextEntry::make('payment_date')
                                ->label('Payment Date')
                                ->date('d F Y')
                                ->description(fn($record) => \Carbon\Carbon::parse($record->payment_date)->diffForHumans()),
                        ]),
                    ]),

                // Invoice & Customer Information
                \Filament\Infolists\Components\Section::make('Invoice & Customer Information')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        \Filament\Infolists\Components\Grid::make(2)->schema([
                            \Filament\Infolists\Components\Group::make()->schema([
                                \Filament\Infolists\Components\TextEntry::make('invoice.invoice_number')
                                    ->label('Invoice Number')
                                    ->copyable(),
                                \Filament\Infolists\Components\TextEntry::make('invoice.invoice_date')
                                    ->label('Invoice Date')
                                    ->date('d F Y'),
                                \Filament\Infolists\Components\TextEntry::make('invoice.total')
                                    ->label('Invoice Total')
                                    ->money('IDR'),
                            ]),
                            \Filament\Infolists\Components\Group::make()->schema([
                                \Filament\Infolists\Components\TextEntry::make('invoice.customer.name')
                                    ->label('Customer Name'),
                                \Filament\Infolists\Components\TextEntry::make('invoice.customer.email')
                                    ->label('Customer Email')
                                    ->copyable(),
                                \Filament\Infolists\Components\TextEntry::make('invoice.status')
                                    ->label('Invoice Status')
                                    ->badge()
                                    ->color(fn(?string $state): string => match ($state) {
                                        'Paid' => 'success',
                                        'Unpaid' => 'warning',
                                        'Overdue' => 'danger',
                                        'Draft' => 'gray',
                                        default => 'secondary',
                                    }),
                            ]),
                        ]),
                    ]),

                // Payment Details Section
                \Filament\Infolists\Components\Section::make('Payment Details')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        \Filament\Infolists\Components\Grid::make(2)->schema([
                            \Filament\Infolists\Components\TextEntry::make('payment_mode')
                                ->label('Payment Method')
                                ->badge(),
                            \Filament\Infolists\Components\TextEntry::make('transaction_id')
                                ->label('Transaction ID')
                                ->placeholder('No transaction ID')
                                ->copyable(),
                        ]),
                        \Filament\Infolists\Components\TextEntry::make('note')
                            ->label('Notes')
                            ->placeholder('No notes')
                            ->columnSpanFull(),
                    ]),

                // Receipt Section
                \Filament\Infolists\Components\Section::make('Receipt')
                    ->icon('heroicon-o-document-check')
                    ->schema([
                        \Filament\Infolists\Components\ImageEntry::make('receipt')
                            ->label('Payment Receipt')
                            ->visibility('private')
                            ->extraAttributes([
                                'class' => 'max-w-lg mx-auto'
                            ])
                            ->placeholder('No receipt uploaded'),
                    ])
                    ->visible(fn($record) => !empty($record->receipt)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'view' => Pages\ViewPayment::route('/{record}'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
