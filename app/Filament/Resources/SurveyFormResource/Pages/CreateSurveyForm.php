<?php

namespace App\Filament\Resources\SurveyFormResource\Pages;

use App\Filament\Resources\SurveyFormResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSurveyForm extends CreateRecord
{
    protected static string $resource = SurveyFormResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data;
    }
}
