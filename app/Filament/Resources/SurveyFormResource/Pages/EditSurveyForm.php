<?php

namespace App\Filament\Resources\SurveyFormResource\Pages;

use App\Filament\Resources\SurveyFormResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSurveyForm extends EditRecord
{
    protected static string $resource = SurveyFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
