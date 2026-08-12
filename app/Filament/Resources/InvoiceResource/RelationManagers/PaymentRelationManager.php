<?php

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentRelationManager extends RelationManager
{
    protected static string $relationship = 'payment';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('amount')
                    ->formatStateUsing(fn($state) => 'Rp. ' . number_format($state, 0, ',', '.'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('payment_mode')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('receipt')
                    ->label('Receipt')
                    ->formatStateUsing(fn($state) => $state ? '<a href="' . asset('storage/' . $state) . '" target="_blank">View Receipt</a>' : 'No Receipt')
                    ->html()
                    ->tooltip('See Receipt')
                    ->searchable(),
                Tables\Columns\TextColumn::make('transaction_id')
                    ->searchable()
                    ->placeholder('N/A'),
            ])
            ->recordUrl(null)
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
