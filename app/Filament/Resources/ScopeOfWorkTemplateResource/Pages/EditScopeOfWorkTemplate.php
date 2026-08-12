<?php

namespace App\Filament\Resources\ScopeOfWorkTemplateResource\Pages;

use App\Filament\Resources\ScopeOfWorkTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditScopeOfWorkTemplate extends EditRecord
{
    protected static string $resource = ScopeOfWorkTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
