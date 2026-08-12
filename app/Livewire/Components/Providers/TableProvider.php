<?php

namespace App\Livewire\Components\Providers;

use App\Livewire\Components\Contracts\TabContentProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder; // Import Builder

class TableProvider implements TabContentProvider
{
    public function render(Model $record, array $config = []): ?string
    {
        $tableConfig = $config['table'] ?? [];
        $query = null;

        if (isset($tableConfig['relationship']) && is_string($tableConfig['relationship'])) {
            $relationshipName = $tableConfig['relationship'];
            if (method_exists($record, $relationshipName)) {
                $query = $record->{$relationshipName}();
            }
        }
        if (!$query || !($query instanceof Builder || $query instanceof \Illuminate\Database\Eloquent\Relations\Relation)) {
            return '<div class="p-4 text-gray-500">Table query not configured or relationship invalid</div>';
        }

        $data = $query->orderBy('id', 'DESC')->get();

        // Detect resource class for the items in the table
        $itemResourceClass = null;
        if ($data->isNotEmpty()) {
            $firstItem = $data->first();
            $modelName = class_basename(get_class($firstItem));
            $resourceClass = "App\\Filament\\Resources\\{$modelName}Resource";
            if (class_exists($resourceClass)) {
                $itemResourceClass = $resourceClass;
            }
        }

        return view('livewire.components.providers.table', [
            'data' => $data,
            'columns' => $tableConfig['columns'] ?? [],
            'record' => $record,
            'config' => $config,
            'itemResourceClass' => $itemResourceClass
        ])->render();
    }
}
