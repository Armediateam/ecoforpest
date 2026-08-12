<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PositionResource\Pages;
use App\Models\Position;
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

class PositionResource extends Resource
{
    protected static ?string $model = Position::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationGroup = 'Organization';

    protected static ?string $cluster = Settings::class;

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Position Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Position Title')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('department_id')
                            ->label('Department')
                            ->relationship('department', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('default_shift_id')
                            ->label('Default Shift')
                            ->relationship('defaultShift', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Use department default')
                            ->helperText('Default shift for employees in this position. Leave empty to use department default.')
                            ->prefixIcon('heroicon-m-clock'),

                        Forms\Components\TextInput::make('level')
                            ->label('Grade/Level')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('salary_grade')
                            ->label('Salary Grade')
                            ->numeric()
                            ->prefix('Rp')
                            ->hidden(fn($context) => !auth()->user()->hasRole('super_admin'))
                            ->dehydrated(fn($state, $get) => filled($state)),
                    ])->columns(2),

                Forms\Components\Section::make('Position Details')
                    ->schema([
                        Forms\Components\RichEditor::make('description')
                            ->label('Description')
                            ->toolbarButtons([
                                'blockquote',
                                'bold',
                                'bulletList',
                                'heading',
                                'italic',
                                'link',
                                'orderedList',
                                'redo',
                                'strike',
                                'undo',
                            ])
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_management')
                            ->label('Management Position')
                            ->helperText('Designates positions with management responsibilities')
                            ->default(false),

                        Forms\Components\Toggle::make('is_leader')
                            ->label('Leader Position')
                            ->helperText('Designates positions with team leader responsibilities')
                            ->default(false),

                        Forms\Components\Toggle::make('active')
                            ->label('Active')
                            ->helperText('Inactive positions will not be available for new assignments')
                            ->default(true),
                    ])->columns(2),

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
                Infolists\Components\Section::make('Position Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('title')
                            ->label('Position Title'),

                        Infolists\Components\TextEntry::make('department.name')
                            ->label('Department'),

                        Infolists\Components\TextEntry::make('defaultShift.name')
                            ->label('Default Shift')
                            ->placeholder('Uses department default'),

                        Infolists\Components\TextEntry::make('level')
                            ->label('Grade/Level'),

                        Infolists\Components\TextEntry::make('salary_grade')
                            ->label('Salary Grade')
                            ->money('IDR')
                            ->visible(fn() => auth()->user()->hasRole('HR Manager')),
                    ])->columns(2),

                Infolists\Components\Section::make('Position Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('description')
                            ->label('Description')
                            ->html()
                            ->columnSpanFull(),

                        Infolists\Components\IconEntry::make('is_management')
                            ->label('Management Position')
                            ->boolean(),

                        Infolists\Components\IconEntry::make('is_leader')
                            ->label('Leader Position')
                            ->boolean(),

                        Infolists\Components\IconEntry::make('active')
                            ->label('Active')
                            ->boolean(),

                        Infolists\Components\TextEntry::make('employees_count')
                            ->label('Total Employees')
                            ->state(fn(Position $record): int => $record->employees()->count()),
                    ])->columns(2),

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
                Tables\Columns\TextColumn::make('title')
                    ->label('Position')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('department.name')
                    ->label('Department')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('defaultShift.name')
                    ->label('Default Shift')
                    ->placeholder('Department default')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('level')
                    ->label('Grade/Level')
                    ->sortable(),

                // Tables\Columns\TextColumn::make('code')
                //     ->label('Code')
                //     ->searchable()
                //     ->toggleable(),

                Tables\Columns\IconColumn::make('is_management')
                    ->label('Management')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_leader')
                    ->label('Leader')
                    ->boolean(),

                Tables\Columns\IconColumn::make('active')
                    ->label('Status')
                    ->boolean(),

                Tables\Columns\TextColumn::make('employees_count')
                    ->label('Employees')
                    ->counts('employees')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('department')
                    ->relationship('department', 'name')
                    ->preload()
                    ->multiple()
                    ->label('Department'),

                Tables\Filters\SelectFilter::make('default_shift')
                    ->relationship('defaultShift', 'name')
                    ->preload()
                    ->label('Default Shift'),

                Tables\Filters\TernaryFilter::make('is_management')
                    ->label('Management Position'),

                Tables\Filters\TernaryFilter::make('is_leader')
                    ->label('Leader Position'),

                Tables\Filters\TernaryFilter::make('active')
                    ->label('Active'),

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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPositions::route('/'),
            'create' => Pages\CreatePosition::route('/create'),
            // 'view' => Pages\ViewPosition::route('/{record}'),
            'edit' => Pages\EditPosition::route('/{record}/edit'),
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
