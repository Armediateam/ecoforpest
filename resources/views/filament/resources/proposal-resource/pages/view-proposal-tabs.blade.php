@php
    $proposalStatus = $record->status;
    $statusText = Illuminate\Support\Str::title($proposalStatus);
    $statusColors = [
        'draft' => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
        'send' => 'bg-blue-100 text-blue-700 dark:bg-blue-700 dark:text-blue-300',
        'open' => 'bg-sky-100 text-sky-700 dark:bg-sky-700 dark:text-sky-300',
        'revised' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-600 dark:text-yellow-300',
        'declined' => 'bg-red-100 text-red-700 dark:bg-red-600 dark:text-red-300',
        'accepted' => 'bg-green-100 text-green-700 dark:bg-green-600 dark:text-green-300',
        // Add other proposal statuses if any, remember to quote the key if it's a reserved word
    'default' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
];
$statusClass = $statusColors[strtolower($proposalStatus)] ?? $statusColors['default'];
@endphp
<x-filament-panels::page>
    <div class="space-y-8">
        <!-- Page Header with Enhanced Design -->
        <div
            class="bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
            <!-- Header Background Gradient -->
            <div
                class="bg-gradient-to-r from-primary-50 to-indigo-50 dark:from-slate-800 dark:to-slate-900 px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="bg-primary-100 dark:bg-primary-900/50 p-3 rounded-full">
                            <svg class="w-6 h-6 text-primary-600 dark:text-primary-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                        </div>

                        <div>
                            <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100 flex items-center">
                                {{ $record->subject }}
                                <span
                                    class="ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                                    {{ $statusText }}
                                </span>
                            </h1>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                                Proposal Details and Management
                            </p>
                        </div>
                    </div>

                    <!-- Dynamic Header Actions -->
                    {{-- @if ($headerActions = $this->getCachedHeaderActions())
                        <div class="flex items-center space-x-3">
                            @foreach ($headerActions as $action)
                                {{ $action }}
                            @endforeach
                        </div>
                    @endif --}}
                </div>
            </div>

            <!-- Lead Stats/Quick Info -->
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
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide">Status</p>
                            <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ $statusText ?? 'N/A' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center space-x-3">
                        <div class="bg-orange-100 dark:bg-orange-900/50 p-2 rounded-lg">
                            <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3a4 4 0 118 0v4m-4 6v6m-4-6h8m-8 0H4a2 2 0 00-2 2v6a2 2 0 002 2h16a2 2 0 002-2v-6a2 2 0 00-2-2h-4">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide">Created</p>
                            <p class="text-sm font-medium text-slate-900 dark:text-slate-100">
                                {{ $record->created_at?->format('M d, Y') ?? 'Unknown' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs Component with Enhanced Container -->
        <livewire:components.resource-tabs-component :record="$record" :configuration="$this->getTabsConfiguration()" :resourceClass="get_class($this)" />
    </div>
</x-filament-panels::page>
