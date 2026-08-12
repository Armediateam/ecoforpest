<?php

namespace App\Filament\Resources\HolidayResource\Widgets;

use App\Models\Holiday;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Model;
use Saade\FilamentFullCalendar\Data\EventData;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class HolidayCalendar extends FullCalendarWidget
{
    public Model | string | null $model = Holiday::class;

    public function fetchEvents(array $fetchInfo): array
    {
        return Holiday::query()
            ->where('date', '>=', $fetchInfo['start'])
            ->where('date', '<=', $fetchInfo['end'])
            ->get()
            ->map(
                fn(Holiday $holiday) => EventData::make()
                    ->id($holiday->id)
                    ->title($holiday->name)
                    ->start($holiday->date)
                    ->allDay()
            )
            ->toArray();
    }

    public function getFormSchema(): array
    {
        return [
            TextInput::make('name')
                ->label('Nama Hari Libur')
                ->required(),

            DatePicker::make('date')
                ->label('Tanggal')
                ->required(),

            Select::make('type')
                ->required()
                ->options([
                    'national' => 'National',
                    'company' => 'Company',
                    'custom' => 'Custom',
                ])
                ->default('national'),

            Textarea::make('description')
                ->label('Deskripsi')
                ->columnSpanFull(),
        ];
    }

    public static function canView(): bool
    {
        return false;
    }
}
