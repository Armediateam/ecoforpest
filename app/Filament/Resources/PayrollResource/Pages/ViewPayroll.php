<?php

namespace App\Filament\Resources\PayrollResource\Pages;

use App\Filament\Resources\PayrollResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;

class ViewPayroll extends ViewRecord
{
    protected static string $resource = PayrollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->icon('heroicon-m-pencil-square')
                ->color('warning'),
            Actions\Action::make('duplicate')
                ->label('Duplicate')
                ->icon('heroicon-m-document-duplicate')
                ->color('gray')
                ->action(function () {
                    $newPayroll = $this->record->replicate();
                    $newPayroll->generated_by = auth()->id();
                    $newPayroll->generated_at = now();
                    $newPayroll->save();

                    \Filament\Notifications\Notification::make()
                        ->title('✅ Payroll Duplicated')
                        ->body("Payroll for {$this->record->employee->name} has been duplicated.")
                        ->success()
                        ->send();

                    return redirect()->route('filament.admin.resources.payrolls.edit', ['record' => $newPayroll]);
                }),
            Actions\Action::make('generate_report')
                ->label('Generate Report')
                ->icon('heroicon-m-document-text')
                ->color('info')
                ->action(function () {
                    \Filament\Notifications\Notification::make()
                        ->title('📊 Report Generation')
                        ->body('Report generation feature will be implemented soon.')
                        ->info()
                        ->send();
                }),
            Actions\DeleteAction::make()
                ->icon('heroicon-m-trash')
                ->color('danger'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $this->getResource()::infolist($infolist);
    }
}
