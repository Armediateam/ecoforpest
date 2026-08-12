<x-filament-panels::page>
    <x-filament::tabs label="Content tabs">

        <x-filament::tabs.item :active="$activeTab === 'calendar'" wire:click="$set('activeTab', 'calendar')"
            icon="heroicon-m-calendar-date-range">
            Calendar
        </x-filament::tabs.item>

        <x-filament::tabs.item :active="$activeTab === 'table'" wire:click="$set('activeTab', 'table')" icon="heroicon-m-table-cells">
            Table
        </x-filament::tabs.item>

    </x-filament::tabs>

    @if ($activeTab === 'calendar')
        <div class="py-4">
            @php
                $statuses = [
                    'Open' => '#3b82f6', // Biru
                    'Pending' => '#8e51ff', // Violet
                    'Hold Confirm' => '#f97316', // Oranye
                    'Confirm' => '#22c55e', // Hijau
                    'Assigned' => '#14b8a6', // Teal
                    'On Progress' => '#0ea5e9', // Biru Langit
                    'Closed' => '#6b7280', // Abu-abu
                    'Cancelled' => '#fb2c36', // Merah
                ];
            @endphp

            <div class="p-4 mb-4 border border-slate-200 rounded-lg dark:border-slate-900">
                <h3 class="mb-2 text-lg font-semibold text-slate-900 dark:text-white">Status</h3>
                <div class="grid grid-cols-2 gap-x-4 gap-y-2 sm:grid-cols-4 lg:grid-cols-8">
                    @foreach ($statuses as $status => $color)
                        <div class="flex items-center space-x-2">
                            <span class="w-4 h-4 rounded-full" style="background-color: {{ $color }};"></span>
                            <span class="text-sm text-slate-600 dark:text-slate-300">{{ $status }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            @livewire(\App\Filament\Resources\WorkOrderResource\Widgets\CalendarWidget::class)
        </div>
    @endif

    @if ($activeTab === 'table')
        <div class="py-4">
            {{ $this->table }}
        </div>
    @endif

</x-filament-panels::page>
