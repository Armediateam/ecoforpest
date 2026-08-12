<?php

namespace App\Filament\Resources\PermitResource\Pages;

use App\Filament\Resources\PermitResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\Permit;

class EditPermit extends EditRecord
{
    protected static string $resource = PermitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
            // Actions\ForceDeleteAction::make(),
            // Actions\RestoreAction::make(),
            Action::make('approve')
                ->label('Approve Permit')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->action(function ($record) {
                    $leave = Permit::find($record->id);

                    $leave->status = 'Approved';
                    $leave->approved_at = today();
                    $leave->save();

                    Notification::make()
                        ->title('Permit Approved')
                        ->success()
                        ->send(auth()->user());

                    return redirect()->to('secret/permits');
                }),
            Action::make('reject')
                ->label('Reject Permit')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->action(function ($record) {
                    $leave = Permit::find($record->id);

                    $leave->status = 'rejected';
                    $leave->approved_at = today();
                    $leave->save();

                    Notification::make()
                        ->title('Permit Rejected')
                        ->success()
                        ->send(auth()->user());

                    return redirect()->to('secret/permits');
                }),
        ];
    }
}
