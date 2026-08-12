<?php

namespace App\Livewire\Components\Providers;

use App\Livewire\Components\Contracts\TabContentProvider;
use Illuminate\Database\Eloquent\Model;
use App\Models\ProposalComment; // Assuming this is your comment model

class CommentsProvider implements TabContentProvider
{
    public function render(Model $record, array $config = []): ?string
    {
        $relationshipName = $config['relationship'] ?? 'comments'; // Default to 'comments' relationship
        $commentModelClass = $config['model'] ?? ProposalComment::class; // Default to ProposalComment

        $comments = collect(); // Default to empty collection

        if (method_exists($record, $relationshipName)) {
            // Eager load the user who made the comment
            $comments = $record->{$relationshipName}()->with('user')->latest()->get();
        }

        return view('livewire.components.providers.comments-list', [
            'comments' => $comments,
            'record' => $record, // The Proposal record
            'config' => $config,
            'commentModelClass' => $commentModelClass
        ])->render();
    }
}