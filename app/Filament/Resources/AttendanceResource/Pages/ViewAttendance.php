<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Afsakar\LeafletMapPicker\LeafletMapPickerEntry;

class ViewAttendance extends ViewRecord
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->icon('heroicon-o-pencil'),
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Employee Information')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('employee.name')
                                    ->label('Employee Name')
                                    ->icon('heroicon-o-user')
                                    ->copyable(),
                                Infolists\Components\TextEntry::make('employee.nik')
                                    ->label('Employee NIK')
                                    ->icon('heroicon-o-identification')
                                    ->copyable(),
                                Infolists\Components\TextEntry::make('employee.position.title')
                                    ->label('Position')
                                    ->icon('heroicon-o-briefcase'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Attendance Details')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('date')
                                    ->label('Attendance Date')
                                    ->date('l, d F Y')
                                    ->icon('heroicon-o-calendar'),
                                Infolists\Components\TextEntry::make('workhours')
                                    ->label('Working Hours')
                                    ->formatStateUsing(function ($record) {
                                        if ($record->clock_in && $record->clock_out) {
                                            $clockIn = \Carbon\Carbon::parse($record->clock_in);
                                            $clockOut = \Carbon\Carbon::parse($record->clock_out);
                                            $hours = floor($clockOut->diffInHours($clockIn));
                                            $minutes = $clockOut->diffInMinutes($clockIn) % 60;
                                            return "{$hours}h {$minutes}m";
                                        }
                                    })
                                    ->default('')
                                    ->icon('heroicon-o-clock'),
                            ]),

                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('clock_in')
                                    ->label('Clock In Time')
                                    ->time('H:i')
                                    ->icon('heroicon-o-arrow-right-circle')
                                    ->color('success'),
                                Infolists\Components\TextEntry::make('clock_out')
                                    ->label('Clock Out Time')
                                    ->time('H:i')
                                    ->icon('heroicon-o-arrow-left-circle')
                                    ->color('danger'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Status Information')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('clock_in_status')
                                    ->label('Clock In Status')
                                    ->badge()
                                    ->color(fn(?string $state): string => match ($state) {
                                        'Hadir' => 'success',
                                        'Terlambat' => 'warning',
                                        'Tidak Hadir' => 'danger',
                                        'Libur' => 'info',
                                        'Belum Mulai Shift' => 'gray',
                                        'Belum Absen' => 'gray',
                                        default => 'secondary',
                                    })
                                    ->formatStateUsing(fn(?string $state): string => match ($state) {
                                        'Hadir' => 'Present',
                                        'Terlambat' => 'Late',
                                        'Tidak Hadir' => 'Absent',
                                        'Libur' => 'Holiday',
                                        'Belum Mulai Shift' => 'Before Shift',
                                        'Belum Absen' => 'Not Clocked In',
                                        default => $state ?? 'Unknown',
                                    }),
                                Infolists\Components\TextEntry::make('clock_out_status')
                                    ->label('Clock Out Status')
                                    ->badge()
                                    ->color(fn(?string $state): string => match ($state) {
                                        'Sudah Clock Out' => 'success',
                                        'Early Clock Out' => 'warning',
                                        'Belum Clock Out' => 'danger',
                                        'Libur' => 'info',
                                        'Tidak Hadir' => 'danger',
                                        'Belum Mulai Shift' => 'gray',
                                        'Belum Absen' => 'gray',
                                        default => 'secondary',
                                    })
                                    ->formatStateUsing(fn(?string $state): string => match ($state) {
                                        'Sudah Clock Out' => 'Clocked Out',
                                        'Early Clock Out' => 'Early Out',
                                        'Belum Clock Out' => 'Not Out',
                                        'Libur' => 'Holiday',
                                        'Tidak Hadir' => 'Absent',
                                        'Belum Mulai Shift' => 'Before Shift',
                                        'Belum Absen' => 'Not Clocked In',
                                        default => $state ?? 'Unknown',
                                    }),
                            ]),
                    ]),

                Infolists\Components\Section::make('Leave Information')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        Infolists\Components\IconEntry::make('is_leave')
                            ->label('On Leave')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('warning')
                            ->falseColor('success'),

                        Infolists\Components\TextEntry::make('leave_type')
                            ->label('Leave Type')
                            ->badge()
                            ->color('warning')
                            ->formatStateUsing(fn(?string $state): string => $state ? ucfirst(str_replace('_', ' ', $state)) : 'Not on leave')
                            ->visible(fn($record) => $record->is_leave),

                        Infolists\Components\TextEntry::make('leave_reason')
                            ->label('Leave Reason')
                            ->columnSpanFull()
                            ->visible(fn($record) => $record->is_leave && $record->leave_reason),
                    ])
                    ->columns(2)
                    ->visible(fn($record) => $record->is_leave),

                Infolists\Components\Section::make('Photos & Evidence')
                    ->icon('heroicon-o-camera')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\ImageEntry::make('image_clock_in')
                                    ->label('Clock In Photo')
                                    ->size(200)
                                    ->visible(fn($record) => $record->image_clock_in),
                                Infolists\Components\ImageEntry::make('image_clock_out')
                                    ->label('Clock Out Photo')
                                    ->size(200)
                                    ->visible(fn($record) => $record->image_clock_out),
                            ]),
                    ])
                    ->visible(fn($record) => $record->image_clock_in || $record->image_clock_out),

                Infolists\Components\Section::make('Location Information')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                LeafletMapPickerEntry::make('coordinate_clock_in')
                                    ->label('Location Clock In')
                                    ->tileProvider('google')
                                    ->hideTileControl()
                                    ->state(function ($record) {
                                        $coordinates = $record->coordinate_clock_in;
                                        return [
                                            'lat' => $coordinates['latitude'],
                                            'lng' => $coordinates['longitude'],
                                        ];
                                    })
                                    ->visible(fn($record) => !empty($record->coordinate_clock_in)),
                                LeafletMapPickerEntry::make('coordinate_clock_out')
                                    ->label('Location Clock Out')
                                    ->tileProvider('google')
                                    ->hideTileControl()
                                    ->state(function ($record) {
                                        $coordinates = $record->coordinate_clock_out;
                                        return [
                                            'lat' => $coordinates['latitude'],
                                            'lng' => $coordinates['longitude'],
                                        ];
                                    })
                                    ->visible(fn($record) => !empty($record->coordinate_clock_out)),
                            ]),
                    ])
                    ->visible(fn($record) => !empty($record->coordinate_clock_in) || !empty($record->coordinate_clock_out)),

                Infolists\Components\Section::make('Additional Information')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Notes')
                            ->columnSpanFull()
                            ->visible(fn($record) => !empty($record->notes)),

                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Created At')
                                    ->dateTime('d M Y, H:i'),
                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('Updated At')
                                    ->dateTime('d M Y, H:i'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
