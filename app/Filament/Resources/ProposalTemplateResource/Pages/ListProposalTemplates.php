<?php

namespace App\Filament\Resources\ProposalTemplateResource\Pages;

use App\Filament\Resources\ProposalTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProposalTemplates extends ListRecords
{
    protected static string $resource = ProposalTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
