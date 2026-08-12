<?php

namespace App\Filament\Resources\PermitResource\Pages;

use App\Filament\Resources\PermitResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;

class CreatePermit extends CreateRecord
{
    protected static string $resource = PermitResource::class;

    protected function afterCreate(): void
    {
        $adminUsers = \App\Models\User::with('roles')
            ->whereHas('roles', function ($query) {
                $query->where('name', 'Admin Reguler');
            })->get();
        foreach ($adminUsers as $admin) {
            Notification::make()
                ->title('Permit Created')
                ->body('The permit has been successfully created.')
                ->success()
                ->actions([
                    Action::make('view')
                        ->label('View Permit')
                        ->url(route('filament.secret.resources.permits.edit', ['record' => $this->record->id])),
                ])
                ->sendToDatabase($admin);
        }
    }
}
