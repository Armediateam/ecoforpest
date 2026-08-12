<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CareerHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'careerHistories';

    protected static ?string $recordTitleAttribute = 'type';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->label('Change Type')
                    ->options([
                        'promotion' => 'Promotion',
                        'demotion' => 'Demotion',
                        'transfer' => 'Transfer/Rotation',
                        'new_hire' => 'New Hire',
                        'termination' => 'Termination',
                        'resignation' => 'Resignation',
                        'retirement' => 'Retirement',
                    ])
                    ->required(),

                Forms\Components\DatePicker::make('effective_date')
                    ->label('Effective Date')
                    ->required(),

                Forms\Components\Select::make('from_position_id')
                    ->label('From Position')
                    ->relationship('fromPosition', 'name')
                    ->searchable()
                    ->preload(),

                Forms\Components\Select::make('to_position_id')
                    ->label('To Position')
                    ->relationship('toPosition', 'name')
                    ->searchable()
                    ->preload()
                    ->requiredIf('type', fn($state) => in_array($state, ['promotion', 'demotion', 'transfer'])),

                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->columnSpanFull(),

                Forms\Components\FileUpload::make('document_path')
                    ->label('Supporting Document')
                    ->directory('career-documents')
                    ->acceptedFileTypes(['application/pdf'])
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => ucfirst($state))
                    ->colors([
                        'success' => 'promotion',
                        'danger' => 'demotion',
                        'primary' => 'transfer',
                        'info' => 'new_hire',
                        'warning' => 'termination',
                        'gray' => ['resignation', 'retirement'],
                    ]),

                Tables\Columns\TextColumn::make('effective_date')
                    ->label('Effective Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('fromPosition.title')
                    ->label('From Position')
                    ->searchable(),

                Tables\Columns\TextColumn::make('toPosition.title')
                    ->label('To Position')
                    ->searchable(),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (!$state || strlen($state) <= 30) {
                            return null;
                        }
                        return $state;
                    }),

                Tables\Columns\IconColumn::make('document_path')
                    ->label('Document')
                    ->boolean()
                    ->getStateUsing(fn($record): bool => $record->document_path !== null),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Change Type')
                    ->options([
                        'promotion' => 'Promotion',
                        'demotion' => 'Demotion',
                        'transfer' => 'Transfer/Rotation',
                        'new_hire' => 'New Hire',
                        'termination' => 'Termination',
                        'resignation' => 'Resignation',
                        'retirement' => 'Retirement',
                    ]),

                Tables\Filters\Filter::make('effective_date')
                    ->form([
                        Forms\Components\DatePicker::make('effective_date_from')
                            ->label('Effective Date From'),
                        Forms\Components\DatePicker::make('effective_date_until')
                            ->label('Effective Date Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['effective_date_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('effective_date', '>=', $date),
                            )
                            ->when(
                                $data['effective_date_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('effective_date', '<=', $date),
                            );
                    }),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['created_by'] = auth()->id();
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['updated_by'] = auth()->id();
                        return $data;
                    }),
                Tables\Actions\DeleteAction::make()
                    ->using(function ($record) {
                        $record->update(['deleted_by' => auth()->id()]);
                        $record->delete();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn(Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
