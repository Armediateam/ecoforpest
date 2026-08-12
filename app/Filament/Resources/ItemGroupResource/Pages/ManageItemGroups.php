<?php

namespace App\Filament\Resources\ItemGroupResource\Pages;

use App\Filament\Resources\ItemGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageItemGroups extends ManageRecords
{
    protected static string $resource = ItemGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
