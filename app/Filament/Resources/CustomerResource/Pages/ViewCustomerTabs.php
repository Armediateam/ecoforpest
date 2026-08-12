<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Resources\Pages\ViewRecord;
use App\Livewire\Components\Support\ResourceTabsConfiguration;
use Filament\Infolists\Infolist;

class ViewCustomerTabs extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected static string $view = 'filament.resources.customer-resource.pages.view-customer-tabs';

    public function getTabsConfiguration(): array
    {
        $configuration = ResourceTabsConfiguration::forCustomer()->toArray();
        return $configuration;
    }

    public function infolist(Infolist $infolist): Infolist
    {
        $resource = static::getResource();
        return $resource::infolist($infolist);
    }
}
