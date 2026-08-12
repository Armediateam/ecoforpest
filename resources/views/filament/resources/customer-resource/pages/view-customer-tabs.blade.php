<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Page Header with Stats -->
        <div
            class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <!-- Header Background Gradient -->
            <div
                class="bg-gradient-to-r from-primary-50 to-indigo-50 dark:from-slate-800 dark:to-slate-900 px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <!-- Customer Icon -->
                        <div class="bg-primary-100 dark:bg-primary-900/50 p-3 rounded-full">
                            <svg class="w-6 h-6 text-primary-600 dark:text-primary-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>

                        <!-- Customer Info -->
                        <div>
                            <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100 flex items-center">
                                {{ $record->name }}
                                <span
                                    class="ml-3 inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full {{ $record->is_active ? 'bg-success-50 text-success-700' : 'bg-danger-50 text-danger-700' }}">
                                    @if ($record->is_active)
                                        <x-heroicon-s-check-circle class="w-4 h-4" />
                                        Active
                                    @else
                                        <x-heroicon-s-x-circle class="w-4 h-4" />
                                        Inactive
                                    @endif
                                </span>
                            </h1>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                                    </path>
                                </svg>
                                {{ $record->company }}
                            </p>
                        </div>
                    </div>

                    <div class="flex space-x-3">
                        <x-filament::button tag="a" :href="$this->getResource()::getUrl('edit', ['record' => $record])" icon="heroicon-o-pencil" color="warning">
                            Edit Customer
                        </x-filament::button>
                    </div>
                </div>
            </div>

            <!-- Customer Stats/Quick Info -->
            <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="flex items-center space-x-3">
                        <div class="bg-blue-100 dark:bg-blue-900/50 p-2 rounded-lg">
                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide">Email</p>
                            <p class="text-sm font-medium text-slate-900 dark:text-slate-100">
                                {{ $record->email ?? 'Not provided' }}</p>
                        </div>
                    </div>

                    <div class="flex items-center space-x-3">
                        <div class="bg-green-100 dark:bg-green-900/50 p-2 rounded-lg">
                            <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide">Phone</p>
                            <p class="text-sm font-medium text-slate-900 dark:text-slate-100">
                                {{ $record->phone ?? 'Not provided' }}</p>
                        </div>
                    </div>

                    <div class="flex items-center space-x-3">
                        <div class="bg-purple-100 dark:bg-purple-900/50 p-2 rounded-lg">
                            <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3a4 4 0 118 0v4m-4 6v6m-4-6h8m-8 0H4a2 2 0 00-2 2v6a2 2 0 002 2h16a2 2 0 002-2v-6a2 2 0 00-2-2h-4">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide">Created</p>
                            <p class="text-sm font-medium text-slate-900 dark:text-slate-100">
                                {{ $record->created_at->format('M d, Y') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contract Stats Grid -->
            <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="flex items-center space-x-3">
                        <div class="bg-blue-100 dark:bg-blue-900/50 p-2 rounded-lg">
                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide">Total
                                Contracts</p>
                            <p class="text-sm font-medium text-slate-900 dark:text-slate-100">
                                {{ $record->contracts()->count() }}</p>
                        </div>
                    </div>

                    <div class="flex items-center space-x-3">
                        <div class="bg-green-100 dark:bg-green-900/50 p-2 rounded-lg">
                            <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide">Active
                                Contracts</p>
                            <p class="text-sm font-medium text-slate-900 dark:text-slate-100">
                                {{ $record->contracts()->where('start_date', '<=', now())->where('end_date', '>=', now())->count() }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center space-x-3">
                        <div class="bg-purple-100 dark:bg-purple-900/50 p-2 rounded-lg">
                            <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide">Total Contract
                                Value</p>
                            <p class="text-sm font-medium text-slate-900 dark:text-slate-100">
                                Rp{{ number_format($record->contracts()->sum('contract_value'), 0, ',', '.') }}</p>
                        </div>
                    </div>

                    <div class="flex items-center space-x-3">
                        <div class="bg-purple-100 dark:bg-purple-900/50 p-2 rounded-lg">
                            <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide">Customer
                                Group</p>
                            <p class="text-sm font-medium text-slate-900 dark:text-slate-100">
                                {{ $record->customerGroup?->name ?? 'No Group' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <livewire:components.resource-tabs-component :record="$record" :configuration="$this->getTabsConfiguration()" :resourceClass="get_class($this)" />
    </div>
</x-filament-panels::page>
