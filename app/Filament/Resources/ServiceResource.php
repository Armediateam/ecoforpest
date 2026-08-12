<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Filament\Resources\ServiceResource\RelationManagers;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\RawJs;
use App\Filament\Clusters\WorkOrders;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $cluster = WorkOrders::class;

    protected static ?int $navigationSort = 14;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Split::make([
                    Forms\Components\Section::make()->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('scheduled')
                            ->required()
                            ->numeric(),
                        Forms\Components\TimePicker::make('jam_buka'),
                        Forms\Components\TimePicker::make('jam_tutup'),
                        Forms\Components\TextInput::make('quota_order_per_day')
                            ->required()
                            ->numeric(),
                        Forms\Components\TextInput::make('minimum_order')
                            ->required()
                            ->numeric(),
                        Forms\Components\TextInput::make('price')
                            ->required()
                            ->prefix('Rp')
                            ->mask(RawJs::make('$money($input, \',\')'))
                            ->stripCharacters('.')
                            ->numeric()
                            ->default(0)
                            ->columnSpanFull(),
                        Forms\Components\TimePicker::make('busy_hour_start'),
                        Forms\Components\TimePicker::make('busy_hour_end'),
                        Forms\Components\TextInput::make('price_busy_hour')
                            ->required()
                            ->prefix('Rp')
                            ->mask(RawJs::make('$money($input, \',\')'))
                            ->stripCharacters('.')
                            ->numeric()
                            ->default(0)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('estimate_time_order')
                            ->required()
                            ->numeric()
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('description')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('image')
                            ->image()
                            ->multiple()
                            ->columnSpanFull(),
                        Forms\Components\Repeater::make('tindakan')
                            ->label('Tindakan')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Tindakan')
                                    ->required(),
                                Forms\Components\TextInput::make('description')
                                    ->label('Deskripsi')
                                    ->required(),
                            ])
                            ->addable()
                            ->deletable()
                            ->reorderable()
                            ->default([])
                            ->columnSpanFull(),
                    ])->columns(2),
                    Forms\Components\Section::make()->schema([
                        Forms\Components\Toggle::make('isScheduled')
                            ->required(),
                        Forms\Components\Toggle::make('isCameraEnabled')
                            ->required(),
                        Forms\Components\Toggle::make('isMinimumOrder')
                            ->required(),
                        Forms\Components\Toggle::make('isEnablePriceBusy')
                            ->required(),
                    ])->grow(false),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\IconColumn::make('isScheduled')
                    ->boolean(),
                Tables\Columns\TextColumn::make('scheduled')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('jam_buka'),
                Tables\Columns\TextColumn::make('jam_tutup'),
                Tables\Columns\TextColumn::make('quota_order_per_day')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('isCameraEnabled')
                    ->boolean(),
                Tables\Columns\IconColumn::make('isMinimumOrder')
                    ->boolean(),
                Tables\Columns\TextColumn::make('minimum_order')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->money()
                    ->sortable(),
                Tables\Columns\IconColumn::make('isEnablePriceBusy')
                    ->boolean(),
                Tables\Columns\TextColumn::make('busy_hour_start'),
                Tables\Columns\TextColumn::make('busy_hour_end'),
                Tables\Columns\TextColumn::make('price_busy_hour')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('estimate_time_order')
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
            // Tambahkan relation manager untuk survey forms
            \App\Filament\Resources\ServiceResource\RelationManagers\SurveyFormsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
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
