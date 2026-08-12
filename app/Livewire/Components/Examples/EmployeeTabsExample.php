<?php

namespace App\Livewire\Components\Examples;

use App\Livewire\Components\Support\ResourceTabsConfiguration;
use App\Models\Employee;

class EmployeeTabsExample
{
    /**
     * Example of how to configure tabs for Employee resource
     */
    public static function getConfiguration(): array
    {
        return ResourceTabsConfiguration::make()
            // Detail tab with info list
            ->addInfolistTab('detail', [], [
                'label' => 'Employee Details',
                'icon' => 'heroicon-o-user',
                'description' => 'Basic employee information'
            ])
            
            // Contracts tab with table
            ->addTableTab('contracts', [
                'query' => fn($record) => $record->contracts(),
                'columns' => [
                    'contract_number',
                    'type',
                    'status',
                    'start_date',
                    'end_date',
                    'actions'
                ]
            ], [
                'label' => 'Contracts',
                'icon' => 'heroicon-o-document-text',
                'description' => 'Employment contracts',
                'badge' => fn($record) => $record->contracts->count()
            ])
            
            // Attendance tab with custom view
            ->addViewTab('attendance', 'filament.components.employee-attendance', [
                'label' => 'Attendance',
                'icon' => 'heroicon-o-clock',
                'description' => 'Attendance records'
            ])
            
            // Career history with custom provider
            ->addProviderTab('career_history', 
                \App\Livewire\Components\Providers\CareerHistoryProvider::class, 
                [
                    'label' => 'Career History',
                    'icon' => 'heroicon-o-academic-cap',
                    'description' => 'Promotions, transfers, and position changes'
                ]
            )
            
            // Custom callback tab
            ->addCallbackTab('performance', function($record) {
                return view('filament.components.employee-performance', [
                    'employee' => $record,
                    'reviews' => $record->performanceReviews()->latest()->take(5)->get()
                ])->render();
            }, [
                'label' => 'Performance',
                'icon' => 'heroicon-o-chart-bar',
                'description' => 'Performance reviews and ratings'
            ])
            
            // Activity logs
            ->addProviderTab('activity_logs', 
                \App\Livewire\Components\Providers\ActivityLogProvider::class, 
                [
                    'label' => 'Activity Logs',
                    'icon' => 'heroicon-o-list-bullet',
                    'description' => 'Employee activity history'
                ]
            )
            
            ->toArray();
    }
}
