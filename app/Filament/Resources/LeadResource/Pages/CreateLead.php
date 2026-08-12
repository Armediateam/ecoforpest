<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;

class CreateLead extends CreateRecord
{
    protected static string $resource = LeadResource::class;

    protected function afterCreate(): void
    {
        $lead = $this->record;

        Notification::make()
            ->title('Created')
            ->success()
            ->send();

        $proposal = $this->record;
        if ($lead->assigned) {
            Notification::make()
                ->title('Lead are assigned')
                ->body('This Lead is assigned to you')
                ->success()
                ->actions([
                    Action::make('view')
                        ->label('View Lead')
                        ->url(route('filament.secret.resources.leads.view', ['record' => $lead->id])),
                ])
                ->sendToDatabase($lead->assigned);
        }
    }
}
