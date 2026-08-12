<?php

namespace App\Filament\Resources\OvertimeResource\Pages;

use App\Filament\Resources\OvertimeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;

class CreateOvertime extends CreateRecord
{
    protected static string $resource = OvertimeResource::class;

    protected function afterCreate(): void
    {
        $adminUsers = \App\Models\User::with('roles')
            ->whereHas('roles', function ($query) {
                $query->where('name', 'Admin Reguler');
            })->get();
        foreach ($adminUsers as $admin) {
            Notification::make()
                ->title('Overtime Created')
                ->body('The overtime has been successfully created.')
                ->success()
                ->actions([
                    Action::make('view')
                        ->label('View Overtime')
                        ->url(route('filament.secret.resources.overtimes.edit', ['record' => $this->record->id])),
                ])
                ->sendToDatabase($admin);
        }
    }
}
