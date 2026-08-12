<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateAttendance extends CreateRecord
{
    protected static string $resource = AttendanceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Attendance record created')
            ->body('The attendance record has been created successfully.')
            ->duration(5000);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
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

    // protected function afterCreate(): void
    // {
    //     // Log the creation if activity log is available
    //     if (function_exists('activity')) {
    //         activity()
    //             ->performedOn($this->record)
    //             ->causedBy(auth()->user())
    //             ->log('Attendance record created');
    //     }
    // }
}
