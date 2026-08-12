<?php

namespace App\Filament\Resources\ToolCategoryResource\Pages;

use App\Filament\Resources\ToolCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageToolCategories extends ManageRecords
{
    protected static string $resource = ToolCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
