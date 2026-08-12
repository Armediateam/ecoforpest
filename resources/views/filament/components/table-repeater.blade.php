@php
    use Filament\Support\Enums\Alignment;
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">

    @php
        $containers = $getChildComponentContainers();

        $addAction = $getAction($getAddActionName());
        $cloneAction = $getAction($getCloneActionName());
        $deleteAction = $getAction($getDeleteActionName());
        $moveDownAction = $getAction($getMoveDownActionName());
        $moveUpAction = $getAction($getMoveUpActionName());
        $reorderAction = $getAction($getReorderActionName());

        $isAddable = $isAddable();
        $isCloneable = $isCloneable();
        $isCollapsible = $isCollapsible();
        $isDeletable = $isDeletable();
        $isReorderable = $isReorderable();
        $isReorderableWithButtons = $isReorderableWithButtons();
        $isReorderableWithDragAndDrop = $isReorderableWithDragAndDrop();

        $statePath = $getStatePath();

        $columnLabels = $getColumnLabels();
        $colStyles = $getColStyles();

    @endphp

    <div {{-- x-data="{ state: $wire.entangle('{{ $getStatePath() }}') }"  --}} x-data="{ isCollapsed: @js($isCollapsed()) }"
        x-on:repeater-collapse.window="$event.detail === '{{ $getStatePath() }}' && (isCollapsed = true)"
        x-on:repeater-expand.window="$event.detail === '{{ $getStatePath() }}' && (isCollapsed = false)"
        {{ $attributes->merge($getExtraAttributes(), escape: false)->class(['bg-white border border-slate-300 shadow-sm rounded-lg relative dark:bg-slate-900 dark:border-slate-600']) }}>

        <div @class([
            'filament-tables-header',
            'flex items-center h-10 overflow-hidden border-b bg-slate-50 rounded-t-xl',
            'dark:bg-slate-900 dark:border-slate-700',
        ])>

            <div class="flex-1"></div>
            @if ($isCollapsible)
                <div>
                    <button x-on:click="isCollapsed = !isCollapsed" type="button" @class([
                        'flex items-center justify-center flex-none w-10 h-10 text-slate-400 transition hover:text-slate-300',
                        'dark:text-slate-400 dark:hover:text-slate-500',
                    ])>
                        <x-heroicon-s-minus-small class="w-4 h-4" x-show="! isCollapsed" />

                        <span class="sr-only" x-show="! isCollapsed">
                            {{ __('forms::components.repeater.buttons.collapse_item.label') }}
                        </span>

                        <x-heroicon-s-plus-small class="w-4 h-4" x-show="isCollapsed" x-cloak />

                        <span class="sr-only" x-show="isCollapsed" x-cloak>
                            {{ __('forms::components.repeater.buttons.expand_item.label') }}
                        </span>
                    </button>
                </div>
            @endif
        </div>

        <div class="{{ $isAddable ? '' : ' py-2' }}">
            <table class="it-table-repeater w-full text-left rtl:text-right table-auto text-sm" x-show="! isCollapsed">
                <thead class="rounded-lg overflow-hidden border-b border-slate-300 dark:border-slate-700">
                    <tr>

                        @foreach ($columnLabels as $columnLabel)
                            {{-- @if ($columnLabel['display']) --}}
                            <th class="it-table-repeater-cell-label px-4 py-2 bg-primary-50/50 text-primary-500 font-medium text-left rtl:text-right uppercase tracking-wider text-xs"
                                @if ($colStyles && isset($colStyles[$columnLabel['component']])) style="{{ $colStyles[$columnLabel['component']] }}" @endif>
                                <span>
                                    {{ $columnLabel['name'] }}
                                </span>
                            </th>
                            {{-- @endif --}}
                        @endforeach

                        @if ($isReorderableWithDragAndDrop || $isReorderableWithButtons || $isCloneable || $isDeletable)
                            <th class="bg-primary-50/50 text-primary-500"></th>
                        @endif
                    </tr>
                </thead>

                <tbody @if ($isReorderable) {{-- :wire:end.stop="'mountFormComponentAction(\'' . $statePath . '\', \'reorder\', { items: $event.target.sortable.toArray() })'" --}}
                       x-sortable @endif>

                    @foreach ($containers as $uuid => $item)

                        <tr class="it-table-repeater-row"
                            x-on:repeater-collapse.window="$event.detail === '{{ $getStatePath() }}' && (isCollapsed = true)"
                            x-on:repeater-expand.window="$event.detail === '{{ $getStatePath() }}' && (isCollapsed = false)"
                            wire:key="{{ $this->getId() }}.{{ $item->getStatePath() }}.{{ $field::class }}.item"
                            x-sortable-item="{{ $uuid }}">

                            @foreach ($item->getComponents() as $component)
                                <td class="it-table-repeater-cell px-1 py-2 align-top"
                                    @if ($component->isHidden() || $component instanceof \Filament\Forms\Components\Hidden) style="display:none" @endif
                                    @if ($colStyles && isset($colStyles[$component->getName()])) style="{{ $colStyles[$component->getName()] }}" @endif>
                                    {{ $component }}
                                </td>
                            @endforeach

                            @if (
                                $isReorderableWithDragAndDrop ||
                                    $isReorderableWithButtons ||
                                    filled($itemLabel) ||
                                    $isCloneable ||
                                    $isDeletable ||
                                    $isCollapsible)
                                <td class="flex items-center gap-x-3 py-2 max-w-20">
                                    @if ($isReorderableWithDragAndDrop || $isReorderableWithButtons)
                                        @if ($isReorderableWithDragAndDrop)
                                            <div x-sortable-handle>
                                                {{ $reorderAction }}
                                            </div>
                                        @endif

                                        @if ($isReorderableWithButtons)
                                            <div class="flex items-center justify-center">
                                                {{ $moveUpAction(['item' => $uuid])->disabled($loop->first) }}
                                            </div>

                                            <div class="flex items-center justify-center">
                                                {{ $moveDownAction(['item' => $uuid])->disabled($loop->last) }}
                                            </div>
                                        @endif
                                    @endif

                                    @if ($isCloneable || $isDeletable)
                                        @if ($cloneAction->isVisible())
                                            <div>
                                                {{ $cloneAction(['item' => $uuid]) }}
                                            </div>
                                        @endif

                                        @if ($isDeletable)
                                            <div>
                                                {{ $deleteAction(['item' => $uuid]) }}
                                            </div>
                                        @endif
                                    @endif

                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>

            </table>

            <div class="p-2 text-xs text-center text-slate-400" x-show="isCollapsed" x-cloak>
                {{ __('filament-table-repeater::components.table-repeater.collapsed') }}
            </div>
        </div>

        @if ($isAddable && $addAction->isVisible())
            <div @class([
                'flex py-2 px-8',
                match ($getAddActionAlignment()) {
                    Alignment::Start, Alignment::Left => 'justify-start',
                    Alignment::Center, null => 'justify-center',
                    Alignment::End, Alignment::Right => 'justify-end',
                    default => $alignment,
                },
            ])>
                {{ $addAction }}
            </div>
        @endif

    </div>

</x-dynamic-component>
