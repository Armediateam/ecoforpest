<?php

namespace App\Filament\Resources\OvertimeResource\Pages;

use App\Filament\Resources\OvertimeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\Action;
use App\Models\Overtime;

class EditOvertime extends EditRecord
{
    protected static string $resource = OvertimeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
            // Actions\ForceDeleteAction::make(),
            // Actions\RestoreAction::make(),
            Action::make('approve')
                ->label('Approve Overtime')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->action(function ($record) {
                    $leave = Overtime::find($record->id);

                    $leave->status = 'approved';
                    $leave->approved_at = today();
                    $leave->save();

                    return redirect()->to('secret/overtimes');
                }),
            Action::make('reject')
                ->label('Reject Overtime')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->action(function ($record) {
                    $leave = Overtime::find($record->id);

                    $leave->status = 'rejected';
                    $leave->approved_at = today();
                    $leave->save();

                    return redirect()->to('secret/overtimes');
                }),
        ];
    }
}
