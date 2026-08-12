<?php

namespace App\Livewire\Components\Contracts;

use Illuminate\Database\Eloquent\Model;

interface TabContentProvider
{
    /**
     * Render the content for a specific tab
     *
     * @param Model $record The model record
     * @param array $config Configuration for this tab
     * @return string|null Rendered HTML content
     */
    public function render(Model $record, array $config = []): ?string;
}
