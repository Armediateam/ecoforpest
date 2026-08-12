<?php

namespace App\Filament\Resources\LeaveResource\Pages;

use App\Filament\Resources\LeaveResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;

class CreateLeave extends CreateRecord
{
    protected static string $resource = LeaveResource::class;

    protected function afterCreate(): void
    {
        $adminUsers = \App\Models\User::with('roles')
            ->whereHas('roles', function ($query) {
                $query->where('name', 'Admin Reguler');
            })->get();
        foreach ($adminUsers as $admin) {
            Notification::make()
                ->title('Leave Created')
                ->body('The leave has been successfully created.')
                ->success()
                ->actions([
                    Action::make('view')
                        ->label('View Leave')
                        ->url(route('filament.secret.resources.leaves.edit', ['record' => $this->record->id])),
                ])
                ->sendToDatabase($admin);
        }
    }
}
