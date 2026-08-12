<?php

namespace App\Filament\Resources\ProposalCustomerResource\Pages;

use App\Filament\Resources\ProposalCustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProposalCustomer extends EditRecord
{
    protected static string $resource = ProposalCustomerResource::class;

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
