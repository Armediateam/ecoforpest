<?php

namespace App\Filament\Resources;

use AbdelhamidErrahmouni\FilamentMonacoEditor\MonacoEditor;
use App\Filament\Resources\ContractTemplateResource\Pages;
use App\Models\ContractTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Clusters\Settings;
use FilamentTiptapEditor\Enums\TiptapOutput;
use FilamentTiptapEditor\TiptapEditor;

class ContractTemplateResource extends Resource
{
    protected static ?string $model = ContractTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Contract';

    protected static ?string $cluster = Settings::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(1)
                    ->schema([
                        Forms\Components\Section::make('Template Information')
                            ->description('Basic information about the contract template')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Template Name')
                                    ->placeholder('e.g., Standard Service Contract')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('subject')
                                    ->label('Default Subject')
                                    ->placeholder('e.g., Service Contract for {customer}')
                                    ->helperText('You can use placeholders like {customer}, {date}, etc.')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->columns(2),

                        Forms\Components\Section::make('Template Content')
                            ->description('Create your contract template using either the visual editor or HTML editor.')
                            ->schema([
                                TiptapEditor::make('content')
                                    ->output(TiptapOutput::Html)
                                    ->extraInputAttributes(['style' => 'min-height: 54rem;'])
                                    ->required()
                                    ->helperText('Available placeholders: {customer}, {customer_name}, {customer_email}, {customer_phone}, {customer_address}, {company}, {company_name}, {date}, {contract_number}, {contract_subject}, {start_date}, {end_date}, {contract_value}, {total_workmanship}, {contract_type}. You can also upload and embed images.')
                                    ->columnSpanFull(),
                            ]),

                        Forms\Components\Section::make('Attachments')
                            ->description('Upload additional files that will be available with this template')
                            ->schema([
                                Forms\Components\FileUpload::make('attachments')
                                    ->disk('public')
                                    ->directory('contract-templates')
                                    ->preserveFilenames()
                                    ->multiple()
                                    ->maxSize(5120) // 5MB
                                    ->acceptedFileTypes([
                                        'application/pdf',
                                        'image/jpeg',
                                        'image/png',
                                        'image/webp',
                                        'application/msword',
                                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                                    ])
                                    ->downloadable()
                                    ->previewable()
                                    ->openable()
                                    ->reorderable()
                                    ->imagePreviewHeight('100')
                                    ->loadingIndicatorPosition('left')
                                    ->removeUploadedFileButtonPosition('right')
                                    ->uploadProgressIndicatorPosition('left')
                                    ->columnSpanFull()
                                    ->helperText('Upload PDFs, images, or Word documents (max 5MB each). Files will be preserved with their original names.'),
                            ])
                            ->collapsible(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Template Name')
                    ->searchable()
                    ->sortable()
                    ->description(fn(ContractTemplate $record): string => $record->subject)
                    ->wrap(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Modified')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
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
            'index' => Pages\ListContractTemplates::route('/'),
            'create' => Pages\CreateContractTemplate::route('/create'),
            'edit' => Pages\EditContractTemplate::route('/{record}/edit'),
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
