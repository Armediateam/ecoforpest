<?php

namespace App\Livewire\Components\Providers;

use App\Livewire\Components\Contracts\TabContentProvider;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class ActivityLogProvider implements TabContentProvider
{
    public function render(Model $record, array $config = []): ?string
    {
        // Get activities for this record with related models
        $activities = Activity::forSubject($record)
            ->with(['causer', 'subject'])
            ->when($record->relationLoaded('status'), function ($query) {
                $query->with('properties');
            })
            ->when($record->relationLoaded('assigned'), function ($query) {
                $query->with('properties');
            })
            ->latest()
            ->take($config['limit'] ?? 20)
            ->get();

        return view('livewire.components.providers.activity-log', [
            'activities' => $activities,
            'record' => $record,
            'config' => $config
        ])->render();
    }
}
