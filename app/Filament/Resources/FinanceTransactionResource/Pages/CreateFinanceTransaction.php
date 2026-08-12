<?php

namespace App\Filament\Resources\FinanceTransactionResource\Pages;

use App\Filament\Resources\FinanceTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFinanceTransaction extends CreateRecord
{
    protected static string $resource = FinanceTransactionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        return $data;
    }
}
