<?php

namespace App\Filament\Resources\ServiceResource\Pages;

use App\Filament\Resources\ServiceResource;
use App\Models\SurveyForm;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;

class ListServiceSurveys extends ListRecords
{
    protected static string $resource = ServiceResource::class;

    public $serviceId;

    public function mount($serviceId = null): void
    {
        $this->serviceId = $serviceId;
    }

    protected function getTableQuery(): ?\Illuminate\Database\Eloquent\Builder
    {
        return SurveyForm::query()->where('service_id', $this->serviceId);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->url(route('filament.resources.survey-forms.create', ['service_id' => $this->serviceId]))
        ];
    }

    public function getTitle(): string
    {
        return 'Survey Forms for Service';
    }
}
