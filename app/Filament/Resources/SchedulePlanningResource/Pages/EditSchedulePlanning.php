<?php

namespace App\Filament\Resources\SchedulePlanningResource\Pages;

use App\Filament\Resources\SchedulePlanningResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSchedulePlanning extends EditRecord
{
    protected static string $resource = SchedulePlanningResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
