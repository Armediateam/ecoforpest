<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContractsRelationManager extends RelationManager
{
    protected static string $relationship = 'contracts';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('contract_number')
                    ->label('Contract Number')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                Forms\Components\Select::make('type')
                    ->label('Contract Type')
                    ->options([
                        'permanent' => 'Permanent',
                        'contract' => 'Kontrak',
                        'probation' => 'Probation',
                        'internship' => 'Internship',
                    ])
                    ->required(),

                Forms\Components\DatePicker::make('start_date')
                    ->label('Start Date')
                    ->required(),

                Forms\Components\DatePicker::make('end_date')
                    ->label('End Date')
                    ->after('start_date')
                    ->requiredIf('type', '!=', 'permanent'),

                Forms\Components\Select::make('position_id')
                    ->label('Position')
                    ->relationship('position', 'title')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->columnSpanFull(),

                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'terminated' => 'Terminated',
                    ])
                    ->required()
                    ->default('draft'),

                Forms\Components\FileUpload::make('document_path')
                    ->label('Contract Document')
                    ->directory('employee-contracts')
                    ->acceptedFileTypes(['application/pdf'])
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('contract_number')
            ->columns([
                Tables\Columns\TextColumn::make('contract_number')
                    ->label('Contract Number')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'permanent' => 'Permanent',
                        'contract' => 'Kontrak',
                        'probation' => 'Probation',
                        'internship' => 'Internship',
                        default => $state,
                    })
                    ->colors([
                        'success' => 'permanent',
                        'primary' => 'fixed_term',
                        'warning' => 'probation',
                        'info' => 'internship',
                        'secondary' => 'extension',
                    ])
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Start Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('End Date')
                    ->date()
                    ->placeholder('-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => ucfirst($state))
                    ->colors([
                        'gray' => 'draft',
                        'success' => 'active',
                        'info' => 'completed',
                        'danger' => 'terminated',
                    ])
                    ->sortable(),

                Tables\Columns\IconColumn::make('document_path')
                    ->label('Document')
                    ->boolean()
                    ->getStateUsing(fn($record): bool => $record->document_path !== null),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Contract Type')
                    ->options([
                        'permanent' => 'Permanent',
                        'fixed_term' => 'Fixed Term (PKWT)',
                        'probation' => 'Probation',
                        'internship' => 'Internship',
                        'extension' => 'Contract Extension',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'terminated' => 'Terminated',
                    ]),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('start_date_from')
                            ->label('Start Date From'),
                        Forms\Components\DatePicker::make('start_date_until')
                            ->label('Start Date Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_date_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('start_date', '>=', $date),
                            )
                            ->when(
                                $data['start_date_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('start_date', '<=', $date),
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
