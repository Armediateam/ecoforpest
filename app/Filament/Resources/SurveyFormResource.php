<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SurveyFormResource\Pages;
use App\Models\SurveyForm;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Clusters\WorkOrders;
use Filament\Tables\Enums\FiltersLayout;

class SurveyFormResource extends Resource
{
    protected static ?string $model = SurveyForm::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    // protected static ?string $navigationGroup = 'Work Order Management';
    protected static ?int $navigationSort = 13;
    protected static ?string $navigationLabel = 'Survey Forms';
    protected static ?string $modelLabel = 'Survey Form';
    protected static ?string $pluralModelLabel = 'Survey Forms';
    protected static ?string $cluster = WorkOrders::class;

    public static function getNavigationBadge(): ?string
    {
        $total = static::getModel()::count();
        $servicesCount = \App\Models\Service::count();
        $expectedTotal = $servicesCount * 3; // 3 types per service

        return "{$total}/{$expectedTotal}";
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Select::make('service_id')
                            ->label('Service')
                            ->options(\App\Models\Service::all()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),

                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('type')
                            ->options([
                                'identification' => 'Identifikasi',
                                'initial_check' => 'Pemeriksaan Awal',
                                'final_check' => 'Pemeriksaan Akhir',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($set, $state) {
                                if (!in_array($state, ['radio', 'checkbox', 'select'])) {
                                    $set('options', null);
                                }
                            })
                            ->rules([
                                function ($get) use ($form) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get, $form) {
                                        $serviceId = $get('service_id');
                                        $record = $form->getRecord();

                                        if ($serviceId && $value) {
                                            $exists = \App\Models\SurveyForm::where('service_id', $serviceId)
                                                ->where('type', $value)
                                                ->when($record, fn($q) => $q->where('id', '!=', $record->id))
                                                ->exists();

                                            if ($exists) {
                                                $typeLabels = [
                                                    'identification' => 'Identifikasi',
                                                    'initial_check' => 'Pemeriksaan Awal',
                                                    'final_check' => 'Pemeriksaan Akhir',
                                                ];

                                                $fail("Service ini sudah memiliki survey form untuk {$typeLabels[$value]}. Silakan pilih type yang berbeda.");
                                            }
                                        }
                                    };
                                },
                            ]),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),

                        Forms\Components\Repeater::make('fields')
                            ->label('Form Fields')
                            ->schema([
                                Forms\Components\TextInput::make('id')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('Field ID')
                                    ->helperText('Unique identifier for this field'),

                                Forms\Components\TextInput::make('label')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('Field Label'),

                                Forms\Components\Select::make('type')
                                    ->options([
                                        'text' => 'Text Input',
                                        'textarea' => 'Text Area',
                                        'number' => 'Number',
                                        'radio' => 'Radio Buttons',
                                        'checkbox' => 'Checkboxes',
                                        'select' => 'Dropdown',
                                        'file' => 'File Upload',
                                        'signature' => 'Digital Signature'
                                    ])
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($set, $state) {
                                        if (!in_array($state, ['radio', 'checkbox', 'select'])) {
                                            $set('options', null);
                                        }
                                    }),

                                Forms\Components\Toggle::make('required')
                                    ->default(true),

                                Forms\Components\Repeater::make('options')
                                    ->schema([
                                        Forms\Components\TextInput::make('key')
                                            ->required()
                                            ->label('Key (unique, lowercase, no space)'),
                                        Forms\Components\TextInput::make('label')
                                            ->required()
                                            ->label('Label (displayed to user)'),
                                    ])
                                    ->visible(
                                        fn(Forms\Get $get) =>
                                        in_array($get('type'), ['radio', 'checkbox', 'select'])
                                    )
                                    ->columnSpanFull()
                                    ->defaultItems(0),

                                Forms\Components\Toggle::make('multiple')
                                    ->visible(
                                        fn(Forms\Get $get) =>
                                        $get('type') === 'file'
                                    )
                                    ->default(false)
                                    ->label('Allow Multiple Files'),
                            ])
                            ->defaultItems(0)
                            ->collapsible()
                            ->itemLabel(
                                fn(array $state): ?string =>
                                $state['label'] ?? null
                            )
                            ->columnSpanFull()
                            ->orderColumn('sort')
                    ])->columnSpan(['lg' => 2]),

                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Placeholder::make('created_at')
                            ->label('Created at')
                            ->content(
                                fn(SurveyForm $record): ?string =>
                                $record->created_at?->diffForHumans()
                            ),

                        Forms\Components\Placeholder::make('updated_at')
                            ->label('Last modified at')
                            ->content(
                                fn(SurveyForm $record): ?string =>
                                $record->updated_at?->diffForHumans()
                            ),
                    ])
                    ->columnSpan(['lg' => 1])
                    ->hidden(fn($record) => $record === null),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\SelectColumn::make('type')
                    ->options([
                        'identification' => 'Identifikasi',
                        'initial_check' => 'Pemeriksaan Awal',
                        'final_check' => 'Pemeriksaan Akhir',
                    ])
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
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
                Tables\Filters\SelectFilter::make('service_id')
                    ->label('Service')
                    ->options(\App\Models\Service::all()->pluck('name', 'id'))
                    ->placeholder('Filter berdasarkan service'),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'identification' => 'Identifikasi',
                        'initial_check' => 'Pemeriksaan Awal',
                        'final_check' => 'Pemeriksaan Akhir',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
                Tables\Filters\TrashedFilter::make(),
            ], layout: FiltersLayout::AboveContent)->filtersFormColumns(2)
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
            ])
            ->defaultSort('service.name', 'asc');
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
            'index' => Pages\ListSurveyForms::route('/'),
            'create' => Pages\CreateSurveyForm::route('/create'),
            'edit' => Pages\EditSurveyForm::route('/{record}/edit'),
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
