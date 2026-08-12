<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContractResource\Pages;
use App\Filament\Resources\ContractResource\RelationManagers;
use App\Models\Contract;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Support\RawJs;

class ContractResource extends Resource
{
    protected static ?string $model = Contract::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('subject')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('customer_id')
                            ->relationship('customer', 'name')
                            ->columnSpanFull(),
                        Forms\Components\Select::make('template_id')
                            ->label('Contract Template')
                            ->relationship('contractTemplate', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('contract_type_id')
                            ->relationship('contractType', 'name')
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('payment_terms')
                                    ->label('Payment Terms')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('description')
                                    ->columnSpanFull(),
                            ]),
                        Forms\Components\TextInput::make('contract_value')
                            ->required()
                            ->prefix('Rp.')
                            ->mask(RawJs::make('$money($input, \',\')'))
                            ->stripCharacters('.')
                            ->numeric(),
                        Forms\Components\TextInput::make('total_workmanship')
                            ->required()
                            ->numeric(),
                        Forms\Components\DatePicker::make('start_date')
                            ->required(),
                        Forms\Components\DatePicker::make('end_date')
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject')
                    ->searchable(),
                Tables\Columns\TextColumn::make('contractTemplate.name')
                    ->label('Template')
                    ->sortable(),
                Tables\Columns\TextColumn::make('contract_value')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_workmanship')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('contractType.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('contractType')
                    ->relationship('contractType', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('start_date')
                    ->form([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Start Date')
                            ->displayFormat('d-m-Y'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_date'],
                                fn(Builder $query, $date): Builder => $query->whereDate('start_date', $date),
                            );
                    }),
                Tables\Filters\Filter::make('end_date')
                    ->form([
                        Forms\Components\DatePicker::make('end_date')
                            ->label('End Date')
                            ->displayFormat('d-m-Y'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['end_date'],
                                fn(Builder $query, $date): Builder => $query->whereDate('end_date', $date),
                            );
                    }),
                Tables\Filters\TrashedFilter::make(),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->url(fn(Contract $record): string => route('contracts.preview', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListContracts::route('/'),
            'create' => Pages\CreateContract::route('/create'),
            'view' => Pages\ViewContract::route('/{record}'),
            'edit' => Pages\EditContract::route('/{record}/edit'),
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
