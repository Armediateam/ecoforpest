# Reusable Resource Tabs Component

This is a reusable custom view component for Filament using Livewire + Blade that provides a tabbed interface for viewing resource details with customizable navigation and content.

## Features

- **Left Navigation Menu**: Customizable sidebar with tabs
- **Dynamic Content Area**: Right side content that changes based on selected tab
- **Multiple Content Types**: Support for views, providers, callbacks, tables, infolists, and forms
- **Pre-built Configurations**: Ready-to-use configurations for Lead and Customer resources
- **Extensible**: Easy to add new tabs and content types
- **Activity Logging**: Built-in support for Spatie Activity Log
- **Responsive Design**: Mobile-friendly with Tailwind CSS

## Usage

### 1. Basic Usage in a Filament Resource Page

```php
<?php

namespace App\Filament\Resources\YourResource\Pages;

use App\Filament\Resources\YourResource;
use Filament\Resources\Pages\ViewRecord;
use App\Livewire\Components\Support\ResourceTabsConfiguration;

class ViewYourResourceTabs extends ViewRecord
{
    protected static string $resource = YourResource::class;
    protected static string $view = 'filament.resources.your-resource.pages.view-tabs';

    public function getTabsConfiguration(): array
    {
        return ResourceTabsConfiguration::make()
            ->addInfolistTab('detail', [], [
                'label' => 'Details',
                'icon' => 'heroicon-o-information-circle',
                'description' => 'Basic information'
            ])
            ->addTableTab('related_data', [
                'query' => fn($record) => $record->relatedModel(),
                'columns' => ['name', 'status', 'created_at']
            ], [
                'label' => 'Related Data',
                'icon' => 'heroicon-o-table-cells'
            ])
            ->toArray();
    }
}
```

### 2. Blade Template for the Page

```blade
<!-- resources/views/filament/resources/your-resource/pages/view-tabs.blade.php -->
<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Page Header -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $record->name }}</h1>
                    <p class="mt-1 text-sm text-gray-600">Resource Details</p>
                </div>
            </div>
        </div>

        <!-- Tabs Component -->
        <livewire:components.resource-tabs-component 
            :record="$record" 
            :configuration="$this->getTabsConfiguration()" 
        />
    </div>
</x-filament-panels::page>
```

### 3. Add Route to Resource

```php
public static function getPages(): array
{
    return [
        'index' => Pages\ListYourResources::route('/'),
        'create' => Pages\CreateYourResource::route('/create'),
        'view' => Pages\ViewYourResource::route('/{record}'),
        'edit' => Pages\EditYourResource::route('/{record}/edit'),
        'view-tabs' => Pages\ViewYourResourceTabs::route('/{record}/tabs'),
    ];
}
```

## Tab Types

### 1. Infolist Tab
Displays detailed information about the record:

```php
->addInfolistTab('detail', [], [
    'label' => 'Details',
    'icon' => 'heroicon-o-information-circle',
    'description' => 'Record information'
])
```

### 2. Table Tab
Shows related data in a table format:

```php
->addTableTab('proposals', [
    'query' => fn($record) => $record->proposals(),
    'columns' => [
        'subject',
        'status',
        'date',
        'actions'
    ]
], [
    'label' => 'Proposals',
    'icon' => 'heroicon-o-document-text',
    'badge' => fn($record) => $record->proposals->count()
])
```

### 3. View Tab
Uses a custom Blade view:

```php
->addViewTab('custom', 'path.to.your.view', [
    'label' => 'Custom Content',
    'icon' => 'heroicon-o-star'
], [
    'extra_data' => 'value'
])
```

### 4. Provider Tab
Uses a custom content provider class:

```php
->addProviderTab('activity_logs', 
    \App\Livewire\Components\Providers\ActivityLogProvider::class, 
    [
        'label' => 'Activity Logs',
        'icon' => 'heroicon-o-clock'
    ]
)
```

### 5. Callback Tab
Uses a closure for dynamic content:

```php
->addCallbackTab('dynamic', function($record) {
    return view('custom.dynamic-content', ['record' => $record])->render();
}, [
    'label' => 'Dynamic Content',
    'icon' => 'heroicon-o-cog'
])
```

## Tab Configuration Options

Each tab can have the following configuration options:

- `label`: Display name of the tab
- `icon`: Heroicon name for the tab icon
- `description`: Description shown in the content area header
- `badge`: Number or text to show as a badge (can be a closure)
- `actions`: Array of action buttons for the tab header

## Creating Custom Content Providers

Implement the `TabContentProvider` interface:

```php
<?php

namespace App\Livewire\Components\Providers;

use App\Livewire\Components\Contracts\TabContentProvider;
use Illuminate\Database\Eloquent\Model;

class CustomProvider implements TabContentProvider
{
    public function render(Model $record, array $config = []): ?string
    {
        // Your custom logic here
        return view('custom.provider-view', [
            'record' => $record,
            'config' => $config
        ])->render();
    }
}
```

## Pre-built Configurations

### Lead Resource
```php
ResourceTabsConfiguration::forLead()
```

Includes:
- Detail (infolist)
- Proposals (table)
- Activity Logs (provider)

### Customer Resource
```php
ResourceTabsConfiguration::forCustomer()
```

Includes:
- Detail (infolist)
- Contacts (table)
- Proposals (table)
- Invoices (table)
- Activity Logs (provider)

## Customization

You can extend the base configuration:

```php
$config = ResourceTabsConfiguration::forLead()
    ->addTableTab('work_orders', [
        'query' => fn($record) => $record->workOrders(),
        'columns' => ['title', 'status', 'date']
    ], [
        'label' => 'Work Orders',
        'icon' => 'heroicon-o-wrench'
    ]);
```

## Advanced Features

### Deep Linking
Tabs support URL hash-based deep linking automatically.

### Refresh Content
Emit the `refreshContent` event to refresh tab content:

```php
$this->dispatch('refreshContent');
```

### Tab Actions
Add action buttons to tab headers:

```php
->addTab('custom', [
    'label' => 'Custom Tab',
    'actions' => [
        [
            'label' => 'Add New',
            'action' => 'createNew',
            'icon' => 'heroicon-o-plus',
            'color' => 'primary'
        ]
    ]
])
```

## Styling

The component uses Tailwind CSS and Filament design tokens. You can customize the appearance by modifying the blade templates or extending the CSS classes.

## Requirements

- Laravel with Livewire
- Filament v3
- Spatie Activity Log (optional, for activity logging features)
- Tailwind CSS
