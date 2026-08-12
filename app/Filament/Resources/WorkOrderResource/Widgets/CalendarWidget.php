<?php

namespace App\Filament\Resources\WorkOrderResource\Widgets;

use Filament\Widgets\Widget;
use App\Models\WorkOrder;
use App\Filament\Resources\WorkOrderResource;
use Saade\FilamentFullCalendar\Data\EventData;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class CalendarWidget extends FullCalendarWidget
{
    // protected static string $view = 'filament.resources.work-order-resource.widgets.calendar-widget';

    public function config(): array
    {
        return [
            'initialView' => 'dayGridMonth',
            'height' => 'auto',
            'dayMaxEvents' => 4,
            'moreLinkClick' => 'popover',
            'eventDisplay' => 'block',
            'displayEventTime' => false,
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,timeGridWeek,timeGridDay',
            ],
            'buttonText' => [
                'today' => 'Today',
                'month' => 'Month',
                'week' => 'Week',
                'day' => 'Day',
            ],
        ];
    }

    public function fetchEvents(array $fetchInfo): array
    {
        return WorkOrder::query()
            ->where('work_date', '>=', $fetchInfo['start'])
            ->where('work_date', '<=', $fetchInfo['end'])
            ->get()
            ->map(function (WorkOrder $wo) {
                $color = match ($wo->status) {
                    'Open' => '#3b82f6',      // Biru
                    'Pending' => '#8e51ff',   // Violet
                    'Hold Confirm' => '#f97316', // Oranye
                    'Confirm' => '#22c55e',   // Hijau
                    'Assigned' => '#14b8a6',  // Teal
                    'On Progress' => '#0ea5e9',   // Biru Langit
                    'Closed' => '#6b7280',    // Abu-abu
                    'Cancelled' => '#fb2c36', // Merah
                    default => '#8b5cf6',   // Default: Ungu (jika ada status lain)
                };

                $visitCount = 0;
                if ($wo->customer_id) {
                    $visitCount = WorkOrder::where('customer_id', $wo->customer_id)
                        ->where('id', '<=', $wo->id)
                        ->count();
                } elseif ($wo->lead_id) {
                    $visitCount = WorkOrder::where('lead_id', $wo->lead_id)
                        ->where('id', '<=', $wo->id)
                        ->count();
                }

                return EventData::make()
                    ->id($wo->id)
                    ->title($wo->customer ? $wo->customer->name : ($wo->lead ? $wo->lead->name : 'Unknown'))
                    ->start($wo->work_date)
                    ->backgroundColor($color)
                    ->borderColor($color)
                    ->url(
                        url: WorkOrderResource::getUrl(name: 'view', parameters: ['record' => $wo]),
                        shouldOpenUrlInNewTab: true
                    )
                    ->extendedProps([
                        'alamat' => $wo->alamat ?? "-",
                        'petugas' => $wo->assigned ? $wo->assigned->name : "-",
                        'helper' => $wo->helpers->isNotEmpty() ? $wo->employee->pluck('name')->implode(', ') : "-",
                        'status' => $wo->status,
                        'status_color' => $color,
                        'visit_type' => 'Kunjungan',
                        'visit_count' => $visitCount,
                    ])
                    ->allDay();
            })
            ->toArray();
    }

    public function eventDidMount(): string
    {
        return <<<JS
            function({ event, el }){
                let escapeHtml = function(value) {
                    return String(value ?? '').replace(/[&<>"']/g, function(character) {
                        return {
                            '&': '&amp;',
                            '<': '&lt;',
                            '>': '&gt;',
                            '"': '&quot;',
                            "'": '&#039;',
                        }[character];
                    });
                };

                let visitType = event.extendedProps.visit_type;
                let visitCount = event.extendedProps.visit_count;
                let alamat = event.extendedProps.alamat;
                let petugas = event.extendedProps.petugas;
                let status = event.extendedProps.status;
                let statusColor = event.extendedProps.status_color || event.backgroundColor;
                let safeTitle = escapeHtml(event.title);
                let safeAlamat = escapeHtml(alamat);
                let safePetugas = escapeHtml(petugas);
                let safeStatus = escapeHtml(status);
                let titleEl = el.querySelector('.fc-event-title');
                
                if (titleEl) {
                    let visitInfo = visitType + ' ke-' + visitCount;
                    let safeVisitInfo = escapeHtml(visitInfo);

                    el.style.setProperty('--wo-status-color', statusColor);
                    titleEl.innerHTML =
                        '<div class="wo-calendar-event">' +
                            '<div class="wo-event-header">' +
                                '<span class="wo-event-title">' + safeTitle + '</span>' +
                                '<span class="wo-event-status" style="background-color:' + statusColor + '">' + safeStatus + '</span>' +
                            '</div>' +
                            '<div class="wo-event-meta wo-event-location">' +
                                '<span class="wo-event-kicker">LOC</span>' +
                                '<span class="wo-event-text">' + safeAlamat + '</span>' +
                            '</div>' +
                            '<div class="wo-event-footer">' +
                                '<span class="wo-event-meta wo-event-assignee">' +
                                    '<span class="wo-event-kicker">PIC</span>' +
                                    '<span class="wo-event-text">' + safePetugas + '</span>' +
                                '</span>' +
                                '<span class="wo-event-visit">' + safeVisitInfo + '</span>' +
                            '</div>' +
                        '</div>';
                    
                    let tooltipText = event.title + '\\n' +
                                    'Status: ' + status + '\\n' +
                                    'Alamat: ' + alamat + '\\n' +
                                    'Petugas: ' + petugas + '\\n' +
                                    visitInfo;
                    
                    el.setAttribute('title', tooltipText);
                    el.setAttribute('aria-label', 'Work Order: ' + event.title + ', Location: ' + alamat + ', Assigned to: ' + petugas + ', ' + visitInfo);
                }
            }
        JS;
    }
}
