<?php

namespace App\Filament\Resources\LeaveResource\Pages;

use App\Filament\Resources\LeaveResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\Leave;

class EditLeave extends EditRecord
{
    protected static string $resource = LeaveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
            // Actions\DeleteAction::make(),
            // Actions\ForceDeleteAction::make(),
            // Actions\RestoreAction::make(),
            Action::make('approve')
                ->label('Approve Leave')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->action(function ($record) {
                    $leave = Leave::find($record->id);

                    $leave->status = 'Approved';
                    $leave->approved_at = today();
                    $leave->save();

                    Notification::make()
                        ->title('Leave Approved')
                        ->success()
                        ->send(auth()->user());

                    return redirect()->to('secret/leaves');
                }),
            Action::make('reject')
                ->label('Reject Leave')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->action(function ($record) {
                    $leave = Leave::find($record->id);

                    $leave->status = 'rejected';
                    $leave->approved_at = today();
                    $leave->save();

                    Notification::make()
                        ->title('Leave Rejected')
                        ->success()
                        ->send(auth()->user());

                    return redirect()->to('secret/leaves');
                }),
        ];
    }
}
