<?php

namespace App\Filament\Resources\FinanceTransactionResource\Pages;

use App\Filament\Resources\FinanceTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFinanceTransaction extends EditRecord
{
    protected static string $resource = FinanceTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['user_id'] = auth()->id();
        return $data;
    }
}
