<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PackageResource\Pages;
use App\Filament\Resources\PackageResource\RelationManagers;
use App\Models\Package;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\RawJs;
use App\Filament\Clusters\Settings;

class PackageResource extends Resource
{
    protected static ?string $model = Package::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Proposal';

    protected static ?string $cluster = Settings::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make([
                    Forms\Components\Select::make('service_id')
                        ->relationship('service', 'name'),
                    Forms\Components\Select::make('package_type_id')
                        ->relationship('packageType', 'name'),
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('point')
                        ->required()
                        ->numeric(),
                    Forms\Components\TextInput::make('estimation_time')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('package_guarantee')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('min_price')
                        ->required()
                        ->prefix('Rp')
                        ->mask(RawJs::make('$money($input, \',\')'))
                        ->stripCharacters('.')
                        ->numeric()
                        ->default(0),
                    Forms\Components\TextInput::make('max_price')
                        ->required()
                        ->prefix('Rp')
                        ->mask(RawJs::make('$money($input, \',\')'))
                        ->stripCharacters('.')
                        ->numeric()
                        ->default(0),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('service.name'),
                Tables\Columns\TextColumn::make('packageType.name'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('point')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('estimation_time')
                    ->searchable(),
                Tables\Columns\TextColumn::make('package_guarantee')
                    ->searchable(),
                Tables\Columns\TextColumn::make('min_price')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_price')
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPackages::route('/'),
            'create' => Pages\CreatePackage::route('/create'),
            'edit' => Pages\EditPackage::route('/{record}/edit'),
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
