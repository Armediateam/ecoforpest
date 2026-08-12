<?php

namespace App\Filament\Resources\PayrollResource\Pages;

use App\Filament\Resources\PayrollResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPayroll extends EditRecord
{
    protected static string $resource = PayrollResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $data['employee_income'] = collect($data['employee_income_view'])
            ->mapWithKeys(function ($item) {
                $key = strtolower(str_replace(' ', '_', $item['name']));
                return [$key => $item['nominal']];
            })->toArray();

        $data['employee_expense'] = collect($data['employee_expense_view'])
            ->mapWithKeys(function ($item) {
                $key = strtolower(str_replace(' ', '_', $item['name']));
                return [$key => $item['nominal']];
            })->toArray();

        $record->update($data);

        return $record;
    }



    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
