<?php

namespace App\Filament\Resources\ProposalTemplateResource\Pages;

use App\Filament\Resources\ProposalTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProposalTemplate extends EditRecord
{
    protected static string $resource = ProposalTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('preview_html')
                ->label('Preview HTML')
                ->icon('heroicon-o-code-bracket')
                ->url(fn($record) => route('proposal-templates.preview-html', $record))
                ->color('success')
                ->openUrlInNewTab(),
            Actions\Action::make('preview_pdf')
                ->label('Preview PDF')
                ->icon('heroicon-o-document')
                ->url(fn($record) => route('proposal-templates.preview', $record))
                ->color('primary')
                ->openUrlInNewTab(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
