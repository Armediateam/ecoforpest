<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DepartmentResource\Pages;
use App\Filament\Resources\DepartmentResource\RelationManagers;
use App\Models\Department;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use App\Filament\Clusters\Settings;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Organization';

    protected static ?string $cluster = Settings::class;

    protected static ?int $navigationSort = -2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Department Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('manager_id')
                            ->relationship('manager', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Department Manager'),

                        Forms\Components\Select::make('default_shift_id')
                            ->label('Default Shift')
                            ->relationship('defaultShift', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('No default shift')
                            ->helperText('Default shift for all positions and employees in this department')
                            ->prefixIcon('heroicon-m-clock'),
                    ])->columns(2),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->maxLength(500)
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Hidden::make('created_by')
                    ->dehydrateStateUsing(fn() => auth()->id())
                    ->hiddenOn(['edit']),

                Forms\Components\Hidden::make('updated_by')
                    ->dehydrateStateUsing(fn() => auth()->id()),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Department Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('Department Name'),

                        Infolists\Components\TextEntry::make('manager.name')
                            ->label('Department Manager'),

                        Infolists\Components\TextEntry::make('defaultShift.name')
                            ->label('Default Shift')
                            ->placeholder('No default shift'),
                    ])->columns(2),

                Infolists\Components\Section::make('Additional Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('description')
                            ->label('Description'),

                        Infolists\Components\TextEntry::make('positions_count')
                            ->label('Total Positions')
                            ->state(fn(Department $record): int => $record->positions()->count()),
                    ]),

                Infolists\Components\Section::make('Metadata')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created On')
                            ->dateTime(),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime(),
                    ])->columns(2)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('manager.name')
                    ->label('Manager')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('defaultShift.name')
                    ->label('Default Shift')
                    ->placeholder('No default')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('positions_count')
                    ->counts('positions')
                    ->label('Positions')
                    ->badge()
                    ->color('info')
                    ->sortable(),

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
                Tables\Filters\SelectFilter::make('default_shift')
                    ->relationship('defaultShift', 'name')
                    ->preload()
                    ->label('Default Shift'),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
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
            RelationManagers\PositionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDepartments::route('/'),
            'create' => Pages\CreateDepartment::route('/create'),
            'view' => Pages\ViewDepartment::route('/{record}'),
            'edit' => Pages\EditDepartment::route('/{record}/edit'),
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
