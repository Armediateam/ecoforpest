<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditAttendance extends EditRecord
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->icon('heroicon-o-eye'),
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash'),
            Actions\ForceDeleteAction::make()
                ->icon('heroicon-o-trash'),
            Actions\RestoreAction::make()
                ->icon('heroicon-o-arrow-path'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Attendance record updated')
            ->body('The attendance record has been updated successfully.')
            ->duration(5000);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Auto-set coordinates format if provided as separate fields
        if (isset($data['coordinate_clock_in']) && is_array($data['coordinate_clock_in'])) {
            $data['coordinate_clock_in'] = [
                'latitude' => $data['coordinate_clock_in']['latitude'] ?? null,
                'longitude' => $data['coordinate_clock_in']['longitude'] ?? null,
            ];
        }

        if (isset($data['coordinate_clock_out']) && is_array($data['coordinate_clock_out'])) {
            $data['coordinate_clock_out'] = [
                'latitude' => $data['coordinate_clock_out']['latitude'] ?? null,
                'longitude' => $data['coordinate_clock_out']['longitude'] ?? null,
            ];
        }

        return $data;
    }

    protected function afterSave(): void
    {
        // Log the update if activity log is available
        // if (function_exists('activity')) {
        //     activity()
        //         ->performedOn($this->record)
        //         ->causedBy(auth()->user())
        //         ->log('Attendance record updated');
        // }
    }
}
