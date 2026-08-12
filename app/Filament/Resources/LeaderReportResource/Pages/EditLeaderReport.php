<?php

namespace App\Filament\Resources\LeaderReportResource\Pages;

use App\Filament\Resources\LeaderReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLeaderReport extends EditRecord
{
    protected static string $resource = LeaderReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
