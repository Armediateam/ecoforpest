<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->label('Address Type')
                    ->options([
                        'home' => 'Home',
                        'domicile' => 'Domicile',
                        'emergency' => 'Emergency Contact',
                    ])
                    ->required(),

                Forms\Components\Textarea::make('address')
                    ->label('Address')
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('city')
                    ->label('City')
                    ->required(),

                Forms\Components\TextInput::make('province')
                    ->label('Province')
                    ->required(),

                Forms\Components\TextInput::make('postal_code')
                    ->label('Postal Code')
                    ->required(),

                Forms\Components\TextInput::make('country')
                    ->label('Country')
                    ->default('Indonesia')
                    ->required(),

                Forms\Components\TextInput::make('phone')
                    ->label('Phone Number')
                    ->tel(),

                Forms\Components\Toggle::make('is_primary')
                    ->label('Primary Address')
                    ->default(false)
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, RelationManager $livewire) {
                        if ($state) {
                            // If making this address primary, update the form to indicate
                            // that you're about to remove primary status from other addresses
                            $livewire->emit('address-set-primary');
                        }
                    }),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('address')
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => ucfirst($state))
                    ->colors([
                        'primary' => 'home',
                        'secondary' => 'domicile',
                        'warning' => 'emergency',
                    ]),

                Tables\Columns\TextColumn::make('address')
                    ->label('Address')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('city')
                    ->label('City')
                    ->searchable(),

                Tables\Columns\TextColumn::make('province')
                    ->label('Province')
                    ->searchable(),

                Tables\Columns\TextColumn::make('postal_code')
                    ->label('Postal Code')
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_primary')
                    ->label('Primary')
                    ->boolean(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'home' => 'Home',
                        'domicile' => 'Domicile',
                        'emergency' => 'Emergency Contact',
                    ]),

                Tables\Filters\TrashedFilter::make()
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['created_by'] = auth()->id();
                        return $data;
                    }),
            ])
            ->actions([
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
