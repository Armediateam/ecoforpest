<?php

namespace App\Livewire\Components\Traits;

use App\Livewire\Components\Support\ResourceTabsConfiguration;

trait HasResourceTabs
{
    /**
     * Get the default tabs configuration
     */
    abstract public function getTabsConfiguration(): array;

    /**
     * Get the tabs view name
     */
    protected function getTabsView(): string
    {
        return 'filament.components.resource-tabs-page';
    }

    /**
     * Create a tabs configuration builder
     */
    protected function makeTabsConfiguration(): ResourceTabsConfiguration
    {
        return ResourceTabsConfiguration::make();
    }

    /**
     * Get the page header data
     */
    protected function getPageHeaderData(): array
    {
        return [
            'title' => $this->getRecordTitle(),
            'subtitle' => $this->getPageSubtitle(),
            'actions' => $this->getHeaderActions(),
        ];
    }

    /**
     * Get the record title for the page header
     */
    protected function getRecordTitle(): string
    {
        return $this->record->name ?? $this->record->title ?? 'Record Details';
    }

    /**
     * Get the page subtitle
     */
    protected function getPageSubtitle(): string
    {
        return class_basename($this->record) . ' Details and Management';
    }

    /**
     * Add a quick shortcut method for creating tabs pages
     */
    public static function makeTabsPage(string $route = '/{record}/tabs'): array
    {
        $class = static::class . 'Tabs';
        
        return [
            'view-tabs' => $class::route($route)
        ];
    }
}
