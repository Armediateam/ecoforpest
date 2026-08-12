<?php

namespace App\Livewire\Components\Providers;

use App\Livewire\Components\Contracts\TabContentProvider;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Livewire\Component;
use ReflectionClass;
use ReflectionMethod;
use Illuminate\View\View;
use Exception;

class InfolistProvider implements TabContentProvider
{
    protected function makeInfolist(Model $record, array $config = []): Infolist
    {
        $infolist = Infolist::make();

        // Set the record
        $infolist->record($record);

        // Try to use the resource's infolist definition if available
        if ($resourceClass = $this->resolveResourceClass($record, $config)) {
            // Check if it's a Resource class with static infolist method
            if (
                method_exists($resourceClass, 'infolist') &&
                (new \ReflectionMethod($resourceClass, 'infolist'))->isStatic()
            ) {
                $infolist = $resourceClass::infolist($infolist);
            }
            // Check if it's a ViewRecord page class that needs an instance
            elseif (is_a($resourceClass, \Filament\Resources\Pages\ViewRecord::class, true)) {
                try {
                    // Create an instance of the page
                    $instance = app($resourceClass);

                    // Set the record property
                    $instance->record = $record;

                    // If the page has its own infolist method, use it
                    if (method_exists($instance, 'infolist')) {
                        $infolist = $instance->infolist($infolist);
                    }
                    // Otherwise try to get infolist from the resource
                    else {
                        $resourceClass = $instance::getResource();
                        if (method_exists($resourceClass, 'infolist')) {
                            $infolist = $resourceClass::infolist($infolist);
                        }
                    }
                } catch (\Exception $e) {
                    // If instantiation fails, try to get the resource class
                    if (method_exists($resourceClass, 'getResource')) {
                        $resourceClass = $resourceClass::getResource();
                        if (method_exists($resourceClass, 'infolist')) {
                            $infolist = $resourceClass::infolist($infolist);
                        }
                    }
                }
            }
        }

        return $infolist;
    }

    public function render(Model $record, array $config = []): ?string
    {
        try {
            // Instead of using a dynamic component, render directly to a blade view
            $infolist = $this->makeInfolist($record, $config);

            // Apply configuration
            if (!empty($config['infolist'])) {
                if (isset($config['infolist']['columns'])) {
                    $infolist->columns($config['infolist']['columns']);
                }
            }

            // Create a view directly with the infolist
            return view('livewire.components.providers.filament-infolist', [
                'infolist' => $infolist,
            ])->render();
        } catch (Exception $e) {
            // Log the error for debugging
            \Illuminate\Support\Facades\Log::error('InfolistProvider error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'record' => get_class($record) . '#' . $record->getKey(),
                'resourceClass' => $config['resourceClass'] ?? null,
            ]);

            // Fallback to simpler view if we encounter an error
            return view('livewire.components.providers.infolist', [
                'record' => $record,
                'config' => $config,
                'error' => $e->getMessage() . ' in ' . basename($e->getFile()) . ':' . $e->getLine()
            ])->render();
        }
    }

    protected function resolveResourceClass(Model $record, array $config = []): ?string
    {
        // First, try to use the resourceClass from config if available
        if (!empty($config['resourceClass'])) {
            return $config['resourceClass'];
        }

        // Otherwise, try to find the corresponding Filament resource
        $modelClass = get_class($record);
        $resourcesNamespace = 'App\\Filament\\Resources';

        // Try to resolve using model name
        $modelName = class_basename($modelClass);
        $resourceClass = "{$resourcesNamespace}\\{$modelName}Resource";

        if (class_exists($resourceClass)) {
            return $resourceClass;
        }

        return null;
    }
}
