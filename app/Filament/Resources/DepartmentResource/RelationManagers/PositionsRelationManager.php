<?php

namespace App\Filament\Resources\DepartmentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PositionsRelationManager extends RelationManager
{
    protected static string $relationship = 'positions';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Position Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Position Title')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('level')
                            ->label('Grade/Level')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('code')
                            ->label('Position Code')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('salary_grade')
                            ->label('Salary Grade')
                            ->numeric()
                            ->prefix('Rp')
                            ->hidden(fn($context) => !auth()->user()->hasRole('HR Manager'))
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

                        Forms\Components\Toggle::make('active')
                            ->label('Active')
                            ->helperText('Inactive positions will not be available for new assignments')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Hidden::make('created_by')
                    ->dehydrateStateUsing(fn() => auth()->id()),

                Forms\Components\Hidden::make('updated_by')
                    ->dehydrateStateUsing(fn() => auth()->id()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Position')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('level')
                    ->label('Grade/Level')
                    ->sortable(),

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_management')
                    ->label('Management')
                    ->boolean(),

                Tables\Columns\IconColumn::make('active')
                    ->label('Status')
                    ->boolean(),

                Tables\Columns\TextColumn::make('employees_count')
                    ->label('Employees')
                    ->counts('employees')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_management')
                    ->label('Management Position'),

                Tables\Filters\TernaryFilter::make('active')
                    ->label('Active'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Auto-set the department_id to be the current related department
                        $data['department_id'] = $this->getOwnerRecord()->id;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('title');
    }
}
