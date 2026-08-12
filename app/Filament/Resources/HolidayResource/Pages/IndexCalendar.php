<?php

namespace App\Filament\Resources\HolidayResource\Pages;

use App\Filament\Resources\HolidayResource;
use Filament\Resources\Pages\ListRecords;

class IndexCalendar extends ListRecords
{
    protected static string $resource = HolidayResource::class;

    protected static string $view = 'filament.resources.holiday-resource.pages.index-calendar';
}
