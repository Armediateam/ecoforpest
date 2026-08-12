<x-filament-panels::page>
    <div class="space-y-8">
        <livewire:components.resource-tabs-component :record="$record" :configuration="$this->getTabsConfiguration()" :resourceClass="get_class($this)" />
    </div>
</x-filament-panels::page>