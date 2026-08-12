<?php

namespace App\Filament\Resources\ItemResource\Pages;

use App\Filament\Resources\ItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use App\Livewire\Components\Support\ResourceTabsConfiguration;

class ViewItem extends ViewRecord
{
    protected static string $resource = ItemResource::class;

    protected static string $view = 'filament.resources.item-resource.pages.view-item-tabs';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function getTabsConfiguration(): array
    {
        return ResourceTabsConfiguration::forItem()->toArray();
    }

    public function infolist(Infolist $infolist): Infolist
    {
        // Get the infolist from the resource class
        $resource = static::getResource();
        return $resource::infolist($infolist);
    }
}
