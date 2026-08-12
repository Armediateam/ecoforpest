<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskResource\Pages;
use App\Filament\Resources\TaskResource\RelationManagers;
use App\Models\Task;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Enums\FiltersLayout;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->columnSpanFull()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('start_date')
                    ->required(),
                Forms\Components\DateTimePicker::make('end_date')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'To Do' => 'To Do',
                        'In Progress' => 'In Progress',
                        'Done' => 'Done',
                        'Cancelled' => 'Cancelled',
                    ])
                    ->default('To Do')
                    ->required(),
                Forms\Components\Select::make('prioritas')
                    ->label('Prioritas')
                    ->options([
                        'Rendah' => 'Rendah',
                        'Sedang' => 'Sedang',
                        'Tinggi' => 'Tinggi',
                        'Sangat Tinggi' => 'Sangat Tinggi',
                    ])
                    ->default('Rendah')
                    ->required(),
                Forms\Components\Repeater::make('taskRecurrence')
                    ->relationship()
                    ->schema([
                        Forms\Components\Select::make('frequency')
                            ->label('Prioritas')
                            ->options([
                                'Daily' => 'Daily',
                                'Weekly' => 'Weekly',
                                'Monthly' => 'Monthly',
                                'Yearly' => 'Yearly',
                            ])
                            ->default('Daily'),
                        Forms\Components\TextInput::make('interval')
                            ->required()
                            ->numeric(),
                        Forms\Components\TextInput::make('days_of_week'),
                        Forms\Components\TextInput::make('days_of_month')
                            ->numeric(),
                        Forms\Components\TextInput::make('total_cycle')
                            ->columnSpanFull()
                            ->numeric(),
                    ])
                    ->columns(2)
                    ->reorderable(false)
                    ->addable(false)
                    ->deletable(false)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('catatan')
                    ->columnSpanFull(),
                Forms\Components\Select::make('task_type')
                    ->label('Tipe Task')
                    ->options([
                        'lead' => 'Lead',
                        'proposal' => 'Proposal',
                        'customer' => 'Customer',
                    ])
                    ->required()
                    ->reactive(),
                Forms\Components\Select::make('proposal_id')
                    ->label('Pilih Proposal')
                    ->relationship('proposal', 'subject')
                    ->visible(fn($get) => $get('task_type') === 'proposal')
                    ->required(fn($get) => $get('task_type') === 'proposal'),
                Forms\Components\Select::make('customer_id')
                    ->label('Pilih Customer')
                    ->relationship('customer', 'name')
                    ->visible(fn($get) => $get('task_type') === 'customer')
                    ->required(fn($get) => $get('task_type') === 'customer'),
                Forms\Components\Select::make('lead_id')
                    ->label('Pilih Lead')
                    ->relationship('lead', 'name')
                    ->visible(fn($get) => $get('task_type') === 'lead')
                    ->required(fn($get) => $get('task_type') === 'lead'),
                Forms\Components\Select::make('viewers')
                    ->label('Viewers')
                    ->relationship('viewers', 'name')
                    ->createOptionForm([
                        Forms\Components\Section::make()
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->columnSpanFull()
                                    ->required(),
                            ])
                    ])
                    ->preload()
                    ->searchable()
                    ->multiple(),
                Forms\Components\Select::make('user_id')
                    ->label('Pilih User')
                    ->relationship('user', 'name')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('prioritas')
                    ->searchable(),
                Tables\Columns\TextColumn::make('proposal.subject')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lead.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Assigned To'),
                Tables\Columns\TextColumn::make('contract.subject')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                Filter::make('status')
                    ->form([
                        Forms\Components\Select::make('status')->label('Status')
                            ->options([
                                'To Do' => 'To Do',
                                'In Progress' => 'In Progress',
                                'Done' => 'Done',
                                'Cancelled' => 'Cancelled',
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['status'] === 'To Do') {
                            return $query->where('status', 'To Do');
                        } else if ($data['status'] === 'In Progress') {
                            return $query->where('status', 'In Progress');
                        } else if ($data['status'] === 'Done') {
                            return $query->where('status', 'Done');
                        } else if ($data['status'] === 'Cancelled') {
                            return $query->where('status', 'Cancelled');
                        }
                        return $query;
                    }),
                Filter::make('priority')
                    ->form([
                        Forms\Components\Select::make('prioritas')
                            ->label('Prioritas')
                            ->options([
                                'Rendah' => 'Rendah',
                                'Sedang' => 'Sedang',
                                'Tinggi' => 'Tinggi',
                                'Sangat Tinggi' => 'Sangat Tinggi',
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['prioritas'] === 'Rendah') {
                            return $query->where('prioritas', 'Rendah');
                        } else if ($data['prioritas'] === 'Sedang') {
                            return $query->where('prioritas', 'Sedang');
                        } else if ($data['prioritas'] === 'Tinggi') {
                            return $query->where('prioritas', 'Tinggi');
                        } else if ($data['prioritas'] === 'Sangat Tinggi') {
                            return $query->where('prioritas', 'Sangat Tinggi');
                        }
                        return $query;
                    }),
                Tables\Filters\Filter::make('created_date')
                    ->form([
                        Forms\Components\DatePicker::make('created_at')
                            ->label('Task Created')
                            ->displayFormat('d-m-Y'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_at'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', $date),
                            );
                    }),
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('lead')
                    ->relationship('lead', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'view' => Pages\ViewTask::route('/{record}'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
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
