<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;

    public function mount(): void
    {
        parent::mount();
        $leadId = request()->get('lead_id');
        $customerId = request()->get('customer_id');
        $proposalId = request()->get('proposal_id');
        if ($leadId) {
            $this->form->fill([
                'task_type' => 'lead',
                'lead_id' => $leadId,
            ]);
        } elseif ($customerId) {
            $this->form->fill([
                'task_type' => 'customer',
                'customer_id' => $customerId,
            ]);
        } elseif ($proposalId) {
            $this->form->fill([
                'task_type' => 'proposal',
                'proposal_id' => $proposalId,
            ]);
        }
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->title('Created')
            ->success()
            ->send();

        $task = $this->record;
        if ($task->user) {
            Notification::make()
                ->title('Tasks are assigned')
                ->body('This tasks is assigned to you')
                ->success()
                ->actions([
                    Action::make('view')
                        ->label('View Task')
                        ->url(route('filament.secret.resources.proposals.view', ['record' => $task->id])),
                ])
                ->sendToDatabase($task->user);
        }
    }
}
