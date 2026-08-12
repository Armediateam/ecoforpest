<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockMovementResource\Pages;
use App\Filament\Resources\StockMovementResource\RelationManagers;
use App\Models\StockMovement;
use App\Models\WorkOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('item_id')
                    ->relationship('item', 'name')
                    ->label('Select Item')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Select::make('transaction_type')
                    ->options([
                        'STOCK_IN' => 'Stock In',
                        'STOCK_OUT' => 'Stock Out',
                        'ADJUSTMENT' => 'Adjustment'
                    ])
                    ->default('STOCK_IN')
                    ->required(),
                Forms\Components\TextInput::make('quantity')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('reference')
                    ->maxLength(255),
                Forms\Components\Select::make('work_order_id')
                    ->label('Select Work Order')
                    ->options(function () {
                        return WorkOrder::with(['customer', 'lead'])->get()
                            ->mapWithKeys(function ($workOrder) {
                                if ($workOrder->customer) {
                                    $visitCount = WorkOrder::where('customer_id', $workOrder->customer->id)
                                        ->where('id', '<=', $workOrder->id)
                                        ->count();
                                    $label = $workOrder->customer->name .
                                        ' Kunjungan - Ke ' . $visitCount . ' (Customer)';
                                    return [$workOrder->id => $label];
                                }
                                if ($workOrder->lead) {
                                    $visitCount = WorkOrder::where('lead_id', $workOrder->lead->id)
                                        ->where('id', '<=', $workOrder->id)
                                        ->count();
                                    $label = $workOrder->lead->name .
                                        ' Kunjungan - Ke ' . $visitCount . ' (Lead)';

                                    return [$workOrder->id => $label];
                                }
                                return [];
                            })
                            ->all();
                    }),
                Forms\Components\Select::make('invoice_id')
                    ->relationship('invoice', 'invoice_number'),
                Forms\Components\Select::make('proposal_id')
                    ->relationship('proposal', 'subject'),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('item.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('transaction_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('reference')
                    ->searchable(),
                Tables\Columns\TextColumn::make('workOrder.customer.name')
                    ->label('Work Order')
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice.ivoice_number')
                    ->sortable(),
                Tables\Columns\TextColumn::make('proposal.subject')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListStockMovements::route('/'),
            'create' => Pages\CreateStockMovement::route('/create'),
            'edit' => Pages\EditStockMovement::route('/{record}/edit'),
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
