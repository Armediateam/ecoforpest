<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ItemResource\Pages;
use App\Filament\Resources\ItemResource\RelationManagers;
use App\Models\Item;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Clusters\Settings;
use Filament\Support\RawJs;

class ItemResource extends Resource
{
    protected static ?string $model = Item::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->description('Enter the basic item details')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Item Code')
                            ->options([
                                'potion' => 'Potion',
                                'tools' => 'Tools',
                            ])
                            ->required()
                            ->placeholder('Select Item Type')
                            ->live()
                            ->default('tools')
                            ->columnSpanFull(),
                        // for tools
                        Forms\Components\Select::make('tool_category_id')
                            ->label('Tool Category')
                            ->relationship('toolCategory', 'name')
                            ->preload()
                            ->searchable()
                            ->placeholder('Select tool category')
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->visible(fn(Forms\Get $get) => $get('type') === 'tools')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('code')
                            ->label('Item Code')
                            ->required()
                            ->maxLength(255)
                            ->unique(Item::class, 'code', ignoreRecord: true)
                            ->placeholder('Enter unique item code'),
                        Forms\Components\TextInput::make('name')
                            ->label('Item Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Enter item name'),
                        Forms\Components\Textarea::make('barcode')
                            ->label('Barcode')
                            ->required()
                            ->placeholder('Enter barcode value')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('sku_code')
                            ->label('SKU Code')
                            ->maxLength(255)
                            ->placeholder('Enter SKU code (optional)'),
                        Forms\Components\TextInput::make('sku_name')
                            ->label('SKU Name')
                            ->maxLength(255)
                            ->placeholder('Enter SKU name (optional)'),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->placeholder('Enter item description (optional)')
                            ->columnSpanFull(),
                        // for type potion
                        Forms\Components\TextInput::make('ingredients')
                            ->maxLength(255)
                            ->placeholder('Enter ingredients (optional)')
                            ->visible(fn(Forms\Get $get) => $get('type') === 'potion'),
                        Forms\Components\TextInput::make('produced_by')
                            ->maxLength(255)
                            ->placeholder('Enter produced by (optional)')
                            ->visible(fn(Forms\Get $get) => $get('type') === 'potion'),
                        Forms\Components\TextInput::make('utility')
                            ->maxLength(255)
                            ->placeholder('Enter utility (optional)')
                            ->visible(fn(Forms\Get $get) => $get('type') === 'potion'),
                        Forms\Components\TextInput::make('application')
                            ->maxLength(255)
                            ->placeholder('Enter application (optional)')
                            ->visible(fn(Forms\Get $get) => $get('type') === 'potion'),
                        // for tools
                        Forms\Components\TextInput::make('good_condition')
                            ->label('Item In Good Condition')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->placeholder('0')
                            ->visible(fn(Forms\Get $get) => $get('type') === 'tools'),
                        Forms\Components\TextInput::make('bad_condition')
                            ->label('Item In Bad Condition')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->placeholder('0')
                            ->visible(fn(Forms\Get $get) => $get('type') === 'tools'),
                    ])->columns(2)->collapsible(),

                Forms\Components\Section::make('Categorization')
                    ->description('Select item group and pricing information')
                    ->schema([
                        Forms\Components\Select::make('item_group_id')
                            ->label('Item Group')
                            ->relationship('itemGroup', 'name')
                            ->preload()
                            ->searchable()
                            ->required()
                            ->placeholder('Select item group')
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->columnSpanFull(),
                    ])->columns(1)->collapsible(),

                Forms\Components\Section::make('Pricing & Inventory')
                    ->description('Set pricing and inventory management settings')
                    ->schema([
                        Forms\Components\TextInput::make('rate')
                            ->label('Selling Rate')
                            ->required()
                            ->prefix('Rp')
                            ->mask(RawJs::make('$money($input, \',\')'))
                            ->stripCharacters('.')
                            ->numeric()
                            ->default(0)
                            ->placeholder('0')
                            ->helperText('The price at which this item will be sold to customers'),
                        Forms\Components\TextInput::make('purchase_price')
                            ->label('Purchase Price')
                            ->required()
                            ->prefix('Rp')
                            ->mask(RawJs::make('$money($input, \',\')'))
                            ->stripCharacters('.')
                            ->numeric()
                            ->default(0)
                            ->placeholder('0')
                            ->helperText('The cost price for purchasing this item'),
                        // for potion
                        Forms\Components\TextInput::make('sell_price')
                            ->label('Selling Price')
                            ->prefix('Rp')
                            ->mask(RawJs::make('$money($input, \',\')'))
                            ->stripCharacters('.')
                            ->numeric()
                            ->default(0)
                            ->placeholder('0')
                            ->helperText('The selling price for this potion item')
                            ->visible(fn(Forms\Get $get) => $get('type') === 'potion'),
                        Forms\Components\TextInput::make('warehouse_price')
                            ->label('Warehouse Price')
                            ->prefix('Rp')
                            ->mask(RawJs::make('$money($input, \',\')'))
                            ->stripCharacters('.')
                            ->numeric()
                            ->default(0)
                            ->placeholder('0')
                            ->helperText('The price from warehouse of this item')
                            ->visible(fn(Forms\Get $get) => $get('type') === 'potion'),
                        Forms\Components\TextInput::make('stock')
                            ->label('Stock')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->columnSpanFull()
                            ->placeholder('0')
                            ->helperText('Total stock available for this item'),
                        Forms\Components\TextInput::make('min_inventory_qty')
                            ->label('Minimum Inventory Quantity')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->placeholder('0')
                            ->live()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                $maxQty = $get('max_inventory_qty');
                                if ($maxQty && $state >= $maxQty) {
                                    $set('max_inventory_qty', $state + 1);
                                }
                            })
                            ->helperText('Minimum stock level before reordering'),
                        Forms\Components\TextInput::make('max_inventory_qty')
                            ->label('Maximum Inventory Quantity')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(100)
                            ->placeholder('100')
                            ->live()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                $minQty = $get('min_inventory_qty');
                                if ($minQty && $state <= $minQty) {
                                    $set('min_inventory_qty', max(0, $state - 1));
                                }
                            })
                            ->rules([
                                fn(Forms\Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                    $minQty = $get('min_inventory_qty');
                                    if ($minQty && $value <= $minQty) {
                                        $fail('The maximum inventory quantity must be greater than minimum inventory quantity (' . $minQty . ').');
                                    }
                                }
                            ])
                            ->helperText('Maximum stock level to maintain'),
                        // for potion
                        Forms\Components\TextInput::make('Expenses')
                            ->label('Expenses')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->columnSpanFull()
                            ->placeholder('0')
                            ->helperText('Exepenses for this item')
                            ->visible(fn(Forms\Get $get) => $get('type') === 'potion'),
                    ])->columns(2)->collapsible(),

                Forms\Components\Section::make('Unit & Tax Configuration')
                    ->description('Configure measurement unit and tax settings')
                    ->schema([
                        Forms\Components\Select::make('unit_id')
                            ->label('Unit of Measurement')
                            ->relationship('unit', 'name')
                            ->preload()
                            ->searchable()
                            ->required()
                            ->placeholder('Select unit')
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                        Forms\Components\Select::make('tax_id')
                            ->label('Tax Category')
                            ->relationship('tax', 'name')
                            ->preload()
                            ->searchable()
                            ->required()
                            ->placeholder('Select tax category')
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('value')
                                    ->numeric()
                                    ->suffix('%'),
                                Forms\Components\TextInput::make('description')
                                    ->maxLength(255)
                                    ->placeholder('Optional description'),
                            ]),
                    ])->columns(2)->collapsible(),

                Forms\Components\Section::make('Attachments')
                    ->description('Upload related documents or images (optional)')
                    ->schema([
                        Forms\Components\FileUpload::make('attachment')
                            ->label('Item Attachments')
                            ->multiple()
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->maxSize(5120) // 5MB
                            ->directory('items')
                            ->helperText('Upload images or PDF documents. Maximum 5MB per file.')
                            ->imageEditor()
                            ->columnSpanFull(),
                    ])->columns(1)->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(30),
                Tables\Columns\TextColumn::make('itemGroup.name')
                    ->label('Group')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                Tables\Columns\TextColumn::make('rate')
                    ->label('Selling Rate')
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('purchase_price')
                    ->label('Purchase Price')
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('stock')
                    ->label('Stock')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->badge(),
                Tables\Columns\TextColumn::make('min_inventory_qty')
                    ->label('Min Qty')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('max_inventory_qty')
                    ->label('Max Qty')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('unit.name')
                    ->label('Unit')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('tax.name')
                    ->label('Tax')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('sku_code')
                    ->label('SKU')
                    ->searchable()
                    ->copyable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Filters\SelectFilter::make('item_group_id')
                    ->label('Item Group')
                    ->relationship('itemGroup', 'name')
                    ->preload(),
                Tables\Filters\SelectFilter::make('unit_id')
                    ->label('Unit')
                    ->relationship('unit', 'name')
                    ->preload(),
                Tables\Filters\SelectFilter::make('tax_id')
                    ->label('Tax Category')
                    ->relationship('tax', 'name')
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('View'),
                Tables\Actions\EditAction::make()
                    ->label('Edit'),
                Tables\Actions\DeleteAction::make()
                    ->label('Delete'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Item Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('type')
                            ->label('Item Type')
                            ->badge()
                            ->color('primary')
                            ->formatStateUsing(fn($state) => ucfirst($state))
                            ->placeholder('Not set'),
                        Infolists\Components\TextEntry::make('toolCategory.name')
                            ->label('Tool Category')
                            ->badge()
                            ->color('success')
                            ->placeholder('Not set')
                            ->visible(fn($record) => $record->type === 'tools'),
                        Infolists\Components\TextEntry::make('code')
                            ->label('Item Code')
                            ->copyable()
                            ->badge()
                            ->color('primary'),
                        Infolists\Components\TextEntry::make('name')
                            ->label('Item Name')
                            ->size('lg')
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('itemGroup.name')
                            ->label('Item Group')
                            ->badge()
                            ->color('success'),
                        Infolists\Components\TextEntry::make('barcode')
                            ->label('Barcode')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('sku_code')
                            ->label('SKU Code')
                            ->copyable()
                            ->placeholder('Not set'),
                        Infolists\Components\TextEntry::make('sku_name')
                            ->label('SKU Name')
                            ->placeholder('Not set'),
                        Infolists\Components\TextEntry::make('ingredients')
                            ->placeholder('Not set')
                            ->visible(fn($record) => $record->type === 'potion'),
                        Infolists\Components\TextEntry::make('produced_by')
                            ->placeholder('Not set')
                            ->visible(fn($record) => $record->type === 'potion'),
                        Infolists\Components\TextEntry::make('utility')
                            ->placeholder('Not set')
                            ->visible(fn($record) => $record->type === 'potion'),
                        Infolists\Components\TextEntry::make('application')
                            ->placeholder('Not set')
                            ->visible(fn($record) => $record->type === 'potion'),
                        Infolists\Components\TextEntry::make('good_condition')
                            ->badge()
                            ->color('success')
                            ->placeholder('Not set')
                            ->visible(fn($record) => $record->type === 'tools'),
                        Infolists\Components\TextEntry::make('bad_condition')
                            ->badge()
                            ->color('warning')
                            ->placeholder('Not set')
                            ->visible(fn($record) => $record->type === 'tools'),
                        Infolists\Components\TextEntry::make('description')
                            ->label('Description')
                            ->placeholder('No description')
                            ->columnSpanFull(),
                    ])->columns(3),

                Infolists\Components\Section::make('Pricing & Inventory')
                    ->schema([
                        Infolists\Components\TextEntry::make('rate')
                            ->label('Selling Rate')
                            ->money('IDR')
                            ->color('success'),
                        Infolists\Components\TextEntry::make('purchase_price')
                            ->label('Purchase Price')
                            ->money('IDR')
                            ->color('warning'),
                        Infolists\Components\TextEntry::make('selling_price')
                            ->money('IDR')
                            ->color('warning')
                            ->visible(fn($record) => $record->type === 'potion')
                            ->placeholder('Not set'),
                        Infolists\Components\TextEntry::make('warehouse_price')
                            ->money('IDR')
                            ->color('warning')
                            ->visible(fn($record) => $record->type === 'potion')
                            ->placeholder('Not set'),
                        Infolists\Components\TextEntry::make('expenses')
                            ->visible(fn($record) => $record->type === 'potion')
                            ->placeholder('Not set'),
                        Infolists\Components\TextEntry::make('stock')
                            ->numeric()
                            ->badge()
                            ->color('info'),
                        Infolists\Components\TextEntry::make('min_inventory_qty')
                            ->label('Minimum Inventory')
                            ->numeric()
                            ->badge()
                            ->color('warning'),
                        Infolists\Components\TextEntry::make('max_inventory_qty')
                            ->label('Maximum Inventory')
                            ->numeric()
                            ->badge()
                            ->color('success'),
                        Infolists\Components\TextEntry::make('unit.name')
                            ->label('Unit')
                            ->badge()
                            ->color('gray'),
                        Infolists\Components\TextEntry::make('tax.name')
                            ->label('Tax Category')
                            ->badge()
                            ->color('info'),
                    ])->columns(3),

                Infolists\Components\Section::make('Attachments')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('attachment')
                            ->label('Item Attachments')
                            ->schema([
                                Infolists\Components\TextEntry::make('filename')
                                    ->placeholder('No attachments'),
                            ]),
                    ])
                    ->visible(fn($record) => !empty($record->attachment)),

                Infolists\Components\Section::make('System Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Updated At')
                            ->dateTime(),
                    ])->columns(2),
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
            'index' => Pages\ListItems::route('/'),
            'create' => Pages\CreateItem::route('/create'),
            'view' => Pages\ViewItem::route('/{record}'),
            'edit' => Pages\EditItem::route('/{record}/edit'),
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
