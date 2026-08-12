<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FinanceTransactionResource\Pages;
use App\Filament\Resources\FinanceTransactionResource\RelationManagers;
use App\Models\FinanceTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\FinanceJournalExport;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use App\Models\FinanceCategory;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Filament\Support\Enums\FontWeight;
use Filament\Support\RawJs;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\ViewAction;

class FinanceTransactionResource extends Resource
{
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['category', 'invoice', 'workorder', 'user']);
    }

    protected static ?string $model = FinanceTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Financial';
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationLabel = 'Finance Transactions';
    protected static ?string $modelLabel = 'Finance Transactions';
    protected static ?string $pluralModelLabel = 'Finance Transactions';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'success';
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Transaksi')
                    ->description('Masukkan detail transaksi keuangan')
                    ->icon('heroicon-m-document-text')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('date')
                                    ->label('Tanggal Transaksi')
                                    ->required()
                                    ->default(now())
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->prefixIcon('heroicon-m-calendar-days')
                                    ->helperText('Pilih tanggal transaksi'),

                                Forms\Components\Select::make('type')
                                    ->label('Tipe Transaksi')
                                    ->options([
                                        'income' => 'Pemasukan',
                                        'expense' => 'Pengeluaran'
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->prefixIcon('heroicon-m-arrows-up-down')
                                    ->helperText('Pilih jenis transaksi'),
                            ]),

                        Forms\Components\TextInput::make('description')
                            ->label('Deskripsi')
                            ->required()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-m-document-text')
                            ->helperText('Jelaskan detail transaksi')
                            ->placeholder('Contoh: Pembayaran invoice #001'),

                        Forms\Components\TextInput::make('amount')
                            ->label('Jumlah')
                            ->required()
                            ->prefix('Rp')
                            ->prefixIcon('heroicon-m-banknotes')
                            ->helperText('Masukkan jumlah dalam Rupiah')
                            ->placeholder('0')
                            ->mask(RawJs::make(<<<'JS'
                                $money($input, ',')
                            JS)),
                    ]),

                Section::make('Referensi & Kategori')
                    ->description('Hubungkan transaksi dengan dokumen atau kategori')
                    ->icon('heroicon-m-link')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('reference')
                                    ->label('Referensi')
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-hashtag')
                                    ->helperText('Nomor referensi atau kode transaksi')
                                    ->placeholder('REF-001'),

                                Forms\Components\Select::make('finance_category_id')
                                    ->label('Kategori')
                                    ->relationship('category', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->prefixIcon('heroicon-m-tag')
                                    ->placeholder('Pilih Kategori')
                                    ->helperText('Pilih kategori transaksi'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('invoice_id')
                                    ->label('Invoice')
                                    ->options(function () {
                                        return \App\Models\Invoice::with(['customer', 'lead'])
                                            ->get()
                                            ->mapWithKeys(function ($invoice) {
                                                $customerInfo = '';

                                                if ($invoice->customer) {
                                                    $customerInfo = $invoice->customer->name;
                                                    if ($invoice->customer->company) {
                                                        $customerInfo .= ' (' . $invoice->customer->company . ')';
                                                    }
                                                } elseif ($invoice->lead) {
                                                    $customerInfo = $invoice->lead->name;
                                                    if ($invoice->lead->company) {
                                                        $customerInfo .= ' (' . $invoice->lead->company . ')';
                                                    }
                                                }

                                                $label = $invoice->invoice_number;
                                                if ($customerInfo) {
                                                    $label .= " - {$customerInfo}";
                                                }

                                                return [$invoice->id => $label];
                                            });
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->prefixIcon('heroicon-m-document-duplicate')
                                    ->placeholder('Pilih Invoice')
                                    ->helperText('Hubungkan dengan invoice (opsional)'),

                                Forms\Components\Select::make('workorder_id')
                                    ->label('Work Order')
                                    ->options(function () {
                                        return \App\Models\WorkOrder::with(['customer', 'lead'])
                                            ->get()
                                            ->mapWithKeys(function ($workOrder) {
                                                $customerInfo = '';

                                                if ($workOrder->customer) {
                                                    $customerInfo = $workOrder->customer->name;
                                                    if ($workOrder->customer->company) {
                                                        $customerInfo .= ' (' . $workOrder->customer->company . ')';
                                                    }
                                                } elseif ($workOrder->lead) {
                                                    $customerInfo = $workOrder->lead->name;
                                                    if ($workOrder->lead->company) {
                                                        $customerInfo .= ' (' . $workOrder->lead->company . ')';
                                                    }
                                                }

                                                $label = "#{$workOrder->id}";
                                                if ($customerInfo) {
                                                    $label .= " - {$customerInfo}";
                                                }

                                                return [$workOrder->id => $label];
                                            });
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->prefixIcon('heroicon-m-clipboard-document-list')
                                    ->placeholder('Pilih Work Order')
                                    ->helperText('Hubungkan dengan work order (opsional)'),
                            ]),
                    ]),

                Section::make('Informasi User')
                    ->description('User yang bertanggung jawab atas transaksi ini')
                    ->icon('heroicon-m-user')
                    ->schema([
                        Forms\Components\TextInput::make('user_id')
                            ->label('User')
                            ->default(fn() => auth()->id())
                            ->formatStateUsing(fn() => auth()->user()->name)
                            ->disabled()
                            ->dehydrated(false)
                            ->prefixIcon('heroicon-m-user')
                            ->helperText('User yang membuat transaksi ini (otomatis terisi)'),
                    ]),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable()
                    ->icon('heroicon-m-calendar-days')
                    ->weight(FontWeight::Medium),

                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->searchable()
                    ->wrap()
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'income' => 'success',
                        'expense' => 'danger',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'income' => 'Pemasukan',
                        'expense' => 'Pengeluaran',
                    })
                    ->icon(fn(string $state): string => match ($state) {
                        'income' => 'heroicon-m-arrow-trending-up',
                        'expense' => 'heroicon-m-arrow-trending-down',
                    }),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah')
                    ->sortable()
                    ->numeric(decimalPlaces: 0)
                    ->weight(FontWeight::Bold)
                    ->color(fn($record): string => $record->type === 'income' ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                Tables\Columns\TextColumn::make('reference')
                    ->label('Referensi')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable()
                    ->icon('heroicon-m-hashtag'),

                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('Invoice')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('workorder.id')
                    ->label('Work Order')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->badge()
                    ->color('warning')
                    ->formatStateUsing(fn($state): string => "WO-{$state}"),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->sortable()
                    ->icon('heroicon-m-user'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipe Transaksi')
                    ->options([
                        'income' => 'Pemasukan',
                        'expense' => 'Pengeluaran',
                    ])
                    ->multiple(),

                SelectFilter::make('finance_category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                SelectFilter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Filter::make('date')
                    ->form([
                        DatePicker::make('from')
                            ->label('Dari Tanggal')
                            ->native(false),
                        DatePicker::make('until')
                            ->label('Sampai Tanggal')
                            ->native(false),
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
                    }),

                Filter::make('amount_range')
                    ->form([
                        Forms\Components\TextInput::make('min_amount')
                            ->label('Jumlah Minimum')
                            ->numeric()
                            ->prefix('Rp'),
                        Forms\Components\TextInput::make('max_amount')
                            ->label('Jumlah Maximum')
                            ->numeric()
                            ->prefix('Rp'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_amount'],
                                fn(Builder $query, $amount): Builder => $query->where('amount', '>=', $amount),
                            )
                            ->when(
                                $data['max_amount'],
                                fn(Builder $query, $amount): Builder => $query->where('amount', '<=', $amount),
                            );
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(2)
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Lihat')
                        ->color('info'),
                    Tables\Actions\EditAction::make()
                        ->label('Edit')
                        ->color('warning'),
                    Tables\Actions\DeleteAction::make()
                        ->label('Hapus')
                        ->color('danger'),
                ])->label('Aksi')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih'),

                    BulkAction::make('export_selected')
                        ->label('Export Terpilih')
                        ->icon('heroicon-m-arrow-down-tray')
                        ->color('success')
                        ->action(function (Collection $records) {
                            return Excel::download(new FinanceJournalExport($records->pluck('id')->toArray()), 'finance_transactions_selected.xlsx');
                        }),

                    BulkAction::make('mark_verified')
                        ->label('Tandai Terverifikasi')
                        ->icon('heroicon-m-check-badge')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $records->each->update(['verified_at' => now()]);
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Verifikasi Transaksi')
                        ->modalDescription('Apakah Anda yakin ingin menandai transaksi terpilih sebagai terverifikasi?'),
                ]),
            ])
            ->defaultSort('date', 'desc')
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
            'index' => Pages\ListFinanceTransactions::route('/'),
            'create' => Pages\CreateFinanceTransaction::route('/create'),
            'edit' => Pages\EditFinanceTransaction::route('/{record}/edit'),
        ];
    }
}
