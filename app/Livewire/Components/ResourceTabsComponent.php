<?php

namespace App\Livewire\Components;

use Livewire\Component;
use Illuminate\Database\Eloquent\Model;
use App\Livewire\Components\Contracts\TabContentProvider;

class ResourceTabsComponent extends Component
{
    public Model $record;
    public string $activeTab = '';
    public array $tabs = [];
    public array $tabContents = [];
    public string $configurationMethod = '';
    public string $resourceClass = '';

    protected $listeners = ['refreshContent' => 'refreshTabContent'];

    public function mount(Model $record, array $configuration = [], string $configurationMethod = '', string $resourceClass = '')
    {
        $this->record = $record;
        $this->tabs = $configuration['tabs'] ?? [];
        $this->tabContents = $configuration['tabContents'] ?? [];
        $this->configurationMethod = $configurationMethod;
        $this->resourceClass = $resourceClass ?: $this->detectResourceClass();

        // Set default active tab to first tab
        if (!empty($this->tabs) && empty($this->activeTab)) {
            $this->activeTab = array_key_first($this->tabs);
        }
    }

    public function setActiveTab(string $tabKey)
    {
        $this->activeTab = $tabKey;
        $this->dispatch('tabChanged', $tabKey);
    }

    public function refreshTabContent()
    {
        // Force refresh content
        $this->dispatch('$refresh');
    }

    public function getTabContent()
    {
        if (!isset($this->tabContents[$this->activeTab])) {
            return null;
        }

        $contentConfig = $this->tabContents[$this->activeTab];

        // Handle different content types
        switch ($contentConfig['type'] ?? 'custom') {
            case 'table':
                return app(\App\Livewire\Components\Providers\TableProvider::class)
                    ->render($this->record, $contentConfig);

            case 'infolist':
                return app(\App\Livewire\Components\Providers\InfolistProvider::class)
                    ->render($this->record, array_merge($contentConfig, [
                        'resourceClass' => $this->resourceClass
                    ]));

            case 'form':
                return "Form content for {$this->activeTab} goes here."; // Placeholder
                break;

            default:
                if (isset($contentConfig['view'])) {
                    return view($contentConfig['view'], [
                        'record' => $this->record,
                        'config' => $contentConfig
                    ])->render();
                }

                if (isset($contentConfig['provider'])) {
                    $provider = app($contentConfig['provider']);
                    if (method_exists($provider, 'render')) {
                        return $provider->render($this->record, $contentConfig);
                    }
                }

                if (isset($contentConfig['callback']) && is_callable($contentConfig['callback'])) {
                    return call_user_func($contentConfig['callback'], $this->record);
                }
        }

        // return $contentConfig['type'];
        return "No content definition found for tab: {$this->activeTab}"; // Pesan fallback
    }

    /**
     * Try to automatically detect the resource class based on the record model
     */
    protected function detectResourceClass(): string
    {
        $modelClass = get_class($this->record);
        $modelName = class_basename($modelClass);
        $resourceClass = "App\\Filament\\Resources\\{$modelName}Resource";

        return class_exists($resourceClass) ? $resourceClass : '';
    }

    public function render()
    {
        return view('livewire.components.resource-tabs', [
            'tabs' => $this->tabs,
            'tabContent' => $this->getTabContent(),
            'record' => $this->record
        ]);
    }
}
