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
                let visitType = event.extendedProps.visit_type;
                let visitCount = event.extendedProps.visit_count;
                let alamat = event.extendedProps.alamat;
                let petugas = event.extendedProps.petugas;
                let titleEl = el.querySelector('.fc-event-title');
                
                if (titleEl) {
                    let visitInfo = visitType + ' ke-' + visitCount;
                    
                    // Enhanced HTML structure with full text (will be truncated by CSS, expanded on hover)
                    titleEl.innerHTML += 
                        '<div class="event-details">' +
                            '<div class="event-location">' +
                                '<svg class="event-icon location-icon" viewBox="0 0 16 16" fill="currentColor">' +
                                    '<path d="M8 0C5.2 0 3 2.2 3 5c0 3.9 5 11 5 11s5-7.1 5-11c0-2.8-2.2-5-5-5zM8 7.5c-1.4 0-2.5-1.1-2.5-2.5S6.6 2.5 8 2.5s2.5 1.1 2.5 2.5S9.4 7.5 8 7.5z"/>' +
                                '</svg>' +
                                '<span class="event-text" data-full-text="' + alamat + '">' + alamat + '</span>' +
                            '</div>' +
                            '<div class="event-assignee">' +
                                '<svg class="event-icon user-icon" viewBox="0 0 16 16" fill="currentColor">' +
                                    '<path d="M8 8c2.2 0 4-1.8 4-4S10.2 0 8 0 4 1.8 4 4s1.8 4 4 4zm0 2c-2.7 0-8 1.3-8 4v2h16v-2c0-2.7-5.3-4-8-4z"/>' +
                                '</svg>' +
                                '<span class="event-text" data-full-text="' + petugas + '">' + petugas + '</span>' +
                            '</div>' +
                            '<div class="event-visit-info">' +
                                '<svg class="event-icon visit-icon" viewBox="0 0 16 16" fill="currentColor">' +
                                    '<path d="M14 2H12V1c0-.6-.4-1-1-1s-1 .4-1 1v1H6V1c0-.6-.4-1-1-1s-1 .4-1 1v1H2C.9 2 0 2.9 0 4v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM2 6h12v8H2V6z"/>' +
                                '</svg>' +
                                '<span class="event-text event-visit-count">' + visitInfo + '</span>' +
                            '</div>' +
                        '</div>';
                    
                    // Add comprehensive tooltip
                    let tooltipText = event.title + '\\n' +
                                    'Alamat: ' + alamat + '\\n' +
                                    'Petugas: ' + petugas + '\\n' +
                                    visitInfo;
                    
                    el.setAttribute('title', tooltipText);
                    el.setAttribute('aria-label', 'Work Order: ' + event.title + ', Location: ' + alamat + ', Assigned to: ' + petugas + ', ' + visitInfo);
                    
                    // Add status indicator
                    let statusIndicator = document.createElement('div');
                    statusIndicator.className = 'event-status-indicator';
                    statusIndicator.style.backgroundColor = event.backgroundColor;
                    el.insertBefore(statusIndicator, el.firstChild);
                    
                    // Enhanced hover with expansion effect
                    el.addEventListener('mouseenter', function() {
                        // Add hover class for CSS to handle
                        this.classList.add('event-expanded');
                        
                        // Temporarily remove text truncation for full display
                        let eventTexts = this.querySelectorAll('.event-text');
                        eventTexts.forEach(function(textEl) {
                            let fullText = textEl.getAttribute('data-full-text');
                            if (fullText) {
                                textEl.textContent = fullText;
                            }
                        });
                    });
                    
                    el.addEventListener('mouseleave', function() {
                        // Remove hover class
                        this.classList.remove('event-expanded');
                        
                        // Restore truncated text for compact display
                        let eventTexts = this.querySelectorAll('.event-text');
                        eventTexts.forEach(function(textEl) {
                            let fullText = textEl.getAttribute('data-full-text');
                            if (fullText) {
                                // Truncate based on element type
                                if (textEl.closest('.event-location')) {
                                    textEl.textContent = fullText.length > 25 ? fullText.substring(0, 25) + '...' : fullText;
                                } else if (textEl.closest('.event-assignee')) {
                                    textEl.textContent = fullText.length > 20 ? fullText.substring(0, 20) + '...' : fullText;
                                } else {
                                    textEl.textContent = fullText;
                                }
                            }
                        });
                    });
                    
                    // Initialize with truncated text
                    let eventTexts = el.querySelectorAll('.event-text');
                    eventTexts.forEach(function(textEl) {
                        let fullText = textEl.getAttribute('data-full-text');
                        if (fullText) {
                            if (textEl.closest('.event-location')) {
                                textEl.textContent = fullText.length > 25 ? fullText.substring(0, 25) + '...' : fullText;
                            } else if (textEl.closest('.event-assignee')) {
                                textEl.textContent = fullText.length > 20 ? fullText.substring(0, 20) + '...' : fullText;
                            }
                        }
                    });
                }
            }
        JS;
    }
}
