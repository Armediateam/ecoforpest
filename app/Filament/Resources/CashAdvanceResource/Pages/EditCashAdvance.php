<?php

namespace App\Filament\Resources\CashAdvanceResource\Pages;

use App\Filament\Resources\CashAdvanceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCashAdvance extends EditRecord
{
    protected static string $resource = CashAdvanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
