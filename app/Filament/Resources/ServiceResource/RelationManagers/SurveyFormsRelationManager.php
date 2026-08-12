<?php
namespace App\Filament\Resources\ServiceResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;

class SurveyFormsRelationManager extends RelationManager
{
    protected static string $relationship = 'surveyForms';
    protected static ?string $recordTitleAttribute = 'name';

    public function form(Forms\Form $form): Forms\Form
    {
        $parentServiceId = $this->getOwnerRecord()->id;
        // Ambil schema array dari SurveyFormResource
        $schema = [
            Forms\Components\Select::make('service_id')
                ->label('Service')
                ->options(\App\Models\Service::all()->pluck('name', 'id'))
                ->default($parentServiceId)
                ->hidden()
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
                ->rule(function ($record) use ($parentServiceId) {
                    $ignoreId = $record?->id ?? 'NULL';
                    return 'unique:survey_forms,type,' . $ignoreId . ',id,service_id,' . $parentServiceId;
                })
                ->label('Tipe Form (1 tipe hanya boleh 1x per service)'),
            Forms\Components\Toggle::make('is_active')
                ->label('Active')
                ->default(true)
                ->required()
                ->dehydrated(true),
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
                        ->visible(fn (Forms\Get $get) => 
                            in_array($get('type'), ['radio', 'checkbox', 'select'])
                        )
                        ->columnSpanFull()
                        ->defaultItems(0),
                    Forms\Components\Toggle::make('multiple')
                        ->visible(fn (Forms\Get $get) => 
                            $get('type') === 'file'
                        )
                        ->default(false)
                        ->label('Allow Multiple Files'),
                ])
                ->defaultItems(0)
                ->collapsible()
                ->itemLabel(fn (array $state): ?string => 
                    $state['label'] ?? null
                )
                ->columnSpanFull()
                ->orderColumn('sort')
        ];
        return $form->schema($schema);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Form Name'),
            Tables\Columns\TextColumn::make('type')->label('Type'),
            Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
        ])
        ->headerActions([
            Tables\Actions\CreateAction::make()
                ->label('Add Survey Form')
                ->mutateFormDataUsing(function (array $data, $livewire) {
                    $data['service_id'] = $livewire->getOwnerRecord()->id;
                    return $data;
                }),
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
        ]);
    }
}
