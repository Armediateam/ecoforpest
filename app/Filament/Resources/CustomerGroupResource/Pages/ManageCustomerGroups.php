<?php

namespace App\Filament\Resources\CustomerGroupResource\Pages;

use App\Filament\Resources\CustomerGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCustomerGroups extends ManageRecords
{
    protected static string $resource = CustomerGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
