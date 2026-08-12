<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScopeOfWorkTemplateResource\Pages;
use App\Filament\Resources\ScopeOfWorkTemplateResource\RelationManagers;
use App\Models\ScopeOfWorkTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use FilamentTiptapEditor\Enums\TiptapOutput;
use FilamentTiptapEditor\TiptapEditor;
use App\Filament\Clusters\WorkOrders;


class ScopeOfWorkTemplateResource extends Resource
{
    protected static ?string $model = ScopeOfWorkTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = WorkOrders::class;

    protected static ?int $navigationSort = 17;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Template Information')
                            ->description('Basic information about the proposal template')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Template Name')
                                    ->placeholder('e.g., Standard Pest Control Proposal')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                Forms\Components\Section::make('Template Content')
                            ->description('Create your proposal template using either the visual editor or HTML editor.')
                            ->schema([
                                TiptapEditor::make('content')
                                    ->output(TiptapOutput::Html)
                                    ->extraInputAttributes(['style' => 'min-height: 54rem;'])
                                    ->helperText('Available placeholders: {customer}, {customer_name}, {customer_email}, {customer_phone}, {customer_address}, {company}, {company_name}, {date}, {proposal_date}, {valid_until}, {proposal_number}, {proposal_subject}, {subtotal}, {discount_fixed}, {discount_percent}, {adjustment}, {total}, {payment_term}, {contract_start}, {contract_end}, {warranty_term}, {warranty_type}, {to}, {email}, {phone}, {address}, {city}, {state}, {zip_code}, {country}. You can also upload and embed images.')
                                    ->required()
                                    ->columnSpanFull(),
                            ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListScopeOfWorkTemplates::route('/'),
            'create' => Pages\CreateScopeOfWorkTemplate::route('/create'),
            'edit' => Pages\EditScopeOfWorkTemplate::route('/{record}/edit'),
        ];
    }
}
