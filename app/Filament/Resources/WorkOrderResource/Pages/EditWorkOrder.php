<?php

namespace App\Filament\Resources\WorkOrderResource\Pages;

use App\Filament\Resources\WorkOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWorkOrder extends EditRecord
{
    protected static string $resource = WorkOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        if (function_exists('activity')) {
            activity()
                ->performedOn($this->record)
                ->causedBy(auth()->user())
                ->log('updated');
        }
    }

    protected function afterDelete(): void
    {
        if (function_exists('activity')) {
            activity()
                ->performedOn($this->record)
                ->causedBy(auth()->user())
                ->log('deleted');
        }
    }
}
