<?php

namespace App\Filament\Resources\ContractTemplateResource\Pages;

use App\Filament\Resources\ContractTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContractTemplate extends EditRecord
{
    protected static string $resource = ContractTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('preview_html')
                ->label('Preview HTML')
                ->icon('heroicon-o-code-bracket')
                ->url(fn ($record) => route('contract-templates.preview-html', $record))
                ->color('success')
                ->openUrlInNewTab(),
            Actions\Action::make('preview_pdf')
                ->label('Preview PDF')
                ->icon('heroicon-o-document')
                ->url(fn ($record) => route('contract-templates.preview', $record))
                ->color('primary')
                ->openUrlInNewTab(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
