<div
    class="overflow-hidden dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 transition-colors duration-300">
    @if ($data->isNotEmpty())
        <div class="overflow-x-auto w-full">
            <table class="w-full divide-y divide-slate-200 dark:divide-slate-700 table-fixed">
                <thead class="bg-slate-50/80 dark:bg-slate-800/80 backdrop-blur-md sticky top-0">
                    <tr>
                        @foreach ($columns as $column)
                            <th scope="col"
                                class="px-4 sm:px-6 py-4 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider border-b border-slate-200 dark:border-slate-700 whitespace-nowrap">
                                <div class="flex items-center space-x-1">
                                    <span>{{ is_string($column) ? ucfirst(str_replace('_', ' ', $column)) : $column['label'] ?? 'Column' }}</span>
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="dark:bg-slate-800/90 divide-y divide-slate-100/80 dark:divide-slate-700/80">
                    @foreach ($data as $item)
                        <tr
                            class="group hover:bg-slate-50/90 dark:hover:bg-slate-700/70 transition-all duration-300 ease-in-out">
                            @foreach ($columns as $column)
                                <td
                                    class="px-4 sm:px-6 py-3.5 text-sm text-slate-800 dark:text-slate-200 whitespace-nowrap">
                                    @if (is_string($column))
                                        @if ($column === 'actions')
                                            <div class="flex items-center gap-2">
                                                @php
                                                    $viewUrl = '#';
                                                    $editUrl = '#';

                                                    if (
                                                        $itemResourceClass &&
                                                        method_exists($itemResourceClass, 'getUrl')
                                                    ) {
                                                        try {
                                                            $viewUrl = $itemResourceClass::getUrl('view', [
                                                                'record' => $item->id,
                                                            ]);
                                                            $editUrl = $itemResourceClass::getUrl('edit', [
                                                                'record' => $item->id,
                                                            ]);
                                                        } catch (\Exception $e) {
                                                            // Fallback to default URLs if resource methods fail
                                                            $viewUrl = '#';
                                                            $editUrl = '#';
                                                        }
                                                    }
                                                @endphp
                                                <x-filament::button tag="a" href="{{ $viewUrl }}"
                                                    size="sm" color="gray" icon="heroicon-o-eye"
                                                    tooltip="View details"
                                                    class="group-hover:scale-105 transition-transform duration-200 shadow-sm hover:shadow-md">
                                                    <span>View</span>
                                                </x-filament::button>
                                                <x-filament::button tag="a" href="{{ $editUrl }}"
                                                    size="sm" color="info" icon="heroicon-o-pencil"
                                                    tooltip="Edit record"
                                                    class="group-hover:scale-105 transition-transform duration-200 shadow-sm hover:shadow-md">
                                                    <span>Edit</span>
                                                </x-filament::button>
                                                @if ($item instanceof \App\Models\Proposal)
                                                    <x-filament::button tag="a"
                                                        href="{{ url('/invoices/create-clone?proposal_id=' . $item->id) }}"
                                                        size="sm" color="success"
                                                        icon="heroicon-o-document-duplicate"
                                                        tooltip="Convert to Invoice"
                                                        class="group-hover:scale-105 transition-transform duration-200 shadow-sm hover:shadow-md">
                                                        <span>Convert</span>
                                                    </x-filament::button>
                                                @endif
                                            </div>
                                        @elseif($column === 'status')
                                            @php
                                                $status = data_get($item, $column);
                                                $statusColors = [
                                                    'draft' => [
                                                        'bg' => 'bg-slate-100 dark:bg-slate-800',
                                                        'text' => 'text-slate-700 dark:text-slate-300',
                                                        'border' => 'border-slate-300 dark:border-slate-600',
                                                    ],
                                                    'send' => [
                                                        'bg' => 'bg-blue-50 dark:bg-blue-900/30',
                                                        'text' => 'text-blue-700 dark:text-blue-300',
                                                        'border' => 'border-blue-200 dark:border-blue-700',
                                                    ],
                                                    'open' => [
                                                        'bg' => 'bg-emerald-50 dark:bg-emerald-900/30',
                                                        'text' => 'text-emerald-700 dark:text-emerald-300',
                                                        'border' => 'border-emerald-200 dark:border-emerald-700',
                                                    ],
                                                    'accepted' => [
                                                        'bg' => 'bg-emerald-50 dark:bg-emerald-900/30',
                                                        'text' => 'text-emerald-700 dark:text-emerald-300',
                                                        'border' => 'border-emerald-200 dark:border-emerald-700',
                                                    ],
                                                    'declined' => [
                                                        'bg' => 'bg-red-50 dark:bg-red-900/30',
                                                        'text' => 'text-red-700 dark:text-red-300',
                                                        'border' => 'border-red-200 dark:border-red-700',
                                                    ],
                                                    'active' => [
                                                        'bg' => 'bg-emerald-50 dark:bg-emerald-900/30',
                                                        'text' => 'text-emerald-700 dark:text-emerald-300',
                                                        'border' => 'border-emerald-200 dark:border-emerald-700',
                                                    ],
                                                    'inactive' => [
                                                        'bg' => 'bg-slate-100 dark:bg-slate-800',
                                                        'text' => 'text-slate-700 dark:text-slate-300',
                                                        'border' => 'border-slate-300 dark:border-slate-600',
                                                    ],
                                                    'pending' => [
                                                        'bg' => 'bg-amber-50 dark:bg-amber-900/30',
                                                        'text' => 'text-amber-700 dark:text-amber-300',
                                                        'border' => 'border-amber-200 dark:border-amber-700',
                                                    ],
                                                    'completed' => [
                                                        'bg' => 'bg-emerald-50 dark:bg-emerald-900/30',
                                                        'text' => 'text-emerald-700 dark:text-emerald-300',
                                                        'border' => 'border-emerald-200 dark:border-emerald-700',
                                                    ],
                                                    'cancelled' => [
                                                        'bg' => 'bg-red-50 dark:bg-red-900/30',
                                                        'text' => 'text-red-700 dark:text-red-300',
                                                        'border' => 'border-red-200 dark:border-red-700',
                                                    ],
                                                ];
                                                $colorScheme = $statusColors[strtolower($status)] ?? [
                                                    'bg' => 'bg-slate-100 dark:bg-slate-800',
                                                    'text' => 'text-slate-700 dark:text-slate-300',
                                                    'border' => 'border-slate-300 dark:border-slate-600',
                                                ];
                                            @endphp
                                            <span
                                                class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium border transition-all duration-200 {{ $colorScheme['bg'] }} {{ $colorScheme['text'] }} {{ $colorScheme['border'] }}">
                                                {{ ucfirst($status) }}
                                            </span>
                                        @elseif(str_contains($column, 'date') || str_contains($column, '_at'))
                                            @php
                                                $date = data_get($item, $column);
                                            @endphp
                                            @if ($date)
                                                <div class="flex items-center text-slate-700 dark:text-slate-300">
                                                    <x-filament::icon icon="heroicon-m-calendar-days"
                                                        class="w-4 h-4 mr-2 text-primary-500 dark:text-primary-400" />
                                                    <span class="font-medium" title="{{ $date }}">
                                                        {{ \Carbon\Carbon::parse($date)->format('M j, Y') }}
                                                    </span>
                                                </div>
                                            @else
                                                <span class="text-slate-400 dark:text-slate-500 italic">No date</span>
                                            @endif
                                        @elseif(str_contains($column, 'amount') ||
                                                str_contains($column, 'value') ||
                                                str_contains($column, 'price') ||
                                                str_contains($column, 'total'))
                                            @php
                                                $amount = data_get($item, $column);
                                            @endphp
                                            @if ($amount)
                                                <div class="flex items-center text-slate-900 dark:text-slate-100">
                                                    <x-filament::icon icon="heroicon-m-currency-dollar"
                                                        class="w-4 h-4 mr-1 text-primary-500 dark:text-primary-400" />
                                                    <span class="font-semibold text-primary-600 dark:text-primary-400">
                                                        Rp{{ number_format($amount, 0, ',', '.') }}
                                                    </span>
                                                </div>
                                            @else
                                                <span class="text-slate-400 dark:text-slate-500 italic">No amount</span>
                                            @endif
                                        @elseif($column === 'is_quotation')
                                            @php
                                                $isQuotation = data_get($item, $column);
                                            @endphp
                                            @if ($isQuotation)
                                                <span
                                                    class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium border border-amber-200 bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-700 transition-all duration-200">
                                                    Quotation
                                                </span>
                                            @else
                                                <span
                                                    class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium border border-slate-300 bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-600 transition-all duration-200">
                                                    Invoice
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-slate-900 dark:text-slate-100">
                                                {{ data_get($item, $column) ?: '-' }}
                                            </span>
                                        @endif
                                    @else
                                        @if (isset($column['callback']) && is_callable($column['callback']))
                                            <div class="text-slate-900 dark:text-slate-100">
                                                {!! call_user_func($column['callback'], $item) !!}
                                            </div>
                                        @else
                                            <span class="text-slate-900 dark:text-slate-100">
                                                {{ data_get($item, $column['field'] ?? 'id') ?: '-' }}
                                            </span>
                                        @endif
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if (method_exists($data, 'links') && $data->hasPages())
            <div class="px-4 py-3 bg-white dark:bg-slate-800/80 border-t border-slate-200 dark:border-slate-700">
                {{ $data->links() }}
            </div>
        @endif
    @else
        <div class="text-center py-16 dark:bg-slate-800 transition-colors duration-300">
            <div class="max-w-sm mx-auto py-8">
                <div
                    class="bg-slate-50 dark:bg-slate-700/50 rounded-full w-8 h-8 flex items-center justify-center mx-auto mb-6 transition-colors duration-300 shadow-inner">
                    <x-filament::icon icon="heroicon-o-table-cells"
                        class="w-4 h-4 text-slate-400 dark:text-slate-500" />
                </div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-2">No data available</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed max-w-xs mx-auto">
                    There are no records to display at the moment.
                </p>
            </div>
        </div>
    @endif
</div>
