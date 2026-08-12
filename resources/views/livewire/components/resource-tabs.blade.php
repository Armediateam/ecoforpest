<div>
    <div class="w-full mx-auto">
        <div class="inline-flex overflow-x-auto">
            <nav class="fi-tabs flex max-w-full gap-x-1 overflow-x-auto mx-auto rounded-xl bg-white p-2 shadow-sm ring-1 ring-slate-950/5 dark:bg-slate-900 dark:ring-white/10"
                aria-label="Tabs">
                @if (!empty($tabs))
                    @foreach ($tabs as $key => $tab)
                        @if (is_array($tab) && isset($tab['label']))
                            <button wire:click="setActiveTab('{{ $key }}')"
                                class="fi-tabs-item group flex items-center justify-center gap-x-1 whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-medium outline-none transition duration-75 bg-slate-50 dark:bg-white/5 {{ $activeTab === $key ? 'fi-active fi-tabs-item-active' : '' }}"
                                role="tab" aria-selected="{{ $activeTab === $key ? 'true' : 'false' }}"
                                id="tab-{{ $key }}">
                                @if (isset($tab['icon']))
                                    <x-filament::icon :icon="$tab['icon']" @class([
                                        'h-4 w-4 flex-shrink-0',
                                        'text-primary-600 dark:text-primary-400' => $activeTab === $key,
                                    ]) />
                                @endif
                                <span @class([
                                    'text-primary-600 dark:text-primary-400' => $activeTab === $key,
                                ])>
                                    {{ $tab['label'] }}
                                </span>
                            </button>
                        @endif
                    @endforeach
                @else
                    <div class="text-sm text-slate-500 p-2">No tabs configured</div>
                @endif

            </nav>
        </div>
        {{-- </div> --}}
    </div>
    <!-- Content Area -->
    <div>
        <div class="p-4 sm:p-6">
            @if ($activeTab && isset($tabs[$activeTab]))
                <!-- Tab Header -->
                @if (isset($tabs[$activeTab]['description']) || isset($tabs[$activeTab]['actions']))
                    <div class="border-b border-slate-200 dark:border-slate-700 pb-4 mb-6">
                        <div
                            class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0">
                            @if (isset($tabs[$activeTab]['description']))
                                <div class="flex-1">
                                    <h2
                                        class="text-xl font-semibold text-slate-900 dark:text-slate-100 flex items-center">
                                        {{ $tabs[$activeTab]['label'] }}
                                    </h2>
                                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                                        {{ $tabs[$activeTab]['description'] }}
                                    </p>
                                </div>
                            @endif

                            <!-- Tab Actions -->
                            @if (isset($tabs[$activeTab]['actions']) && is_array($tabs[$activeTab]['actions']))
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($tabs[$activeTab]['actions'] as $action)
                                        @if (is_array($action))
                                            @php
                                                $actionUrl = $action['url'] ?? '#';
                                                // Handle URL templates by replacing {record.id} with actual record ID
                                                if (isset($action['url_template'])) {
                                                    $actionUrl = str_replace(
                                                        '{record.id}',
                                                        $record->id,
                                                        $action['url_template'],
                                                    );
                                                }
                                            @endphp
                                            <x-filament::button tag="a" href="{{ $actionUrl }}"
                                                :color="$action['color'] ?? 'primary'" :size="$action['size'] ?? 'sm'" :icon="$action['icon'] ?? null">
                                                {{ $action['label'] ?? 'Action' }}
                                            </x-filament::button>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Tab Content -->
                <div class="tab-content">
                    @if ($tabContent)
                        <div class="pt-4 max-w-none">
                            {!! $tabContent !!}
                        </div>
                    @else
                        <div class="text-center py-16">
                            <div class="mx-auto max-w-md">
                                <x-filament::icon icon="heroicon-o-document-text"
                                    class="mx-auto h-16 w-16 text-slate-300 dark:text-slate-600" />
                                <h3 class="mt-4 text-lg font-medium text-slate-900 dark:text-slate-100">No content
                                    available</h3>
                                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                                    Content for this tab is not configured yet.
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <div class="text-center py-16">
                    <div class="mx-auto max-w-md">
                        <x-filament::icon icon="heroicon-o-exclamation-triangle"
                            class="mx-auto h-16 w-16 text-slate-300 dark:text-slate-600" />
                        <h3 class="mt-4 text-lg font-medium text-slate-900 dark:text-slate-100">No tab selected</h3>
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                            Please select a tab from the navigation menu above.
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@script
    <script>
        // Enhanced tab management with accessibility and UX improvements
        $wire.on('tabChanged', (tabKey) => {
            // Update URL hash for deep linking
            if (window.history.replaceState) {
                window.history.replaceState(null, null, '#' + tabKey);
            }

            // Announce tab change for screen readers
            const announcement = document.createElement('div');
            announcement.setAttribute('aria-live', 'polite');
            announcement.setAttribute('aria-atomic', 'true');
            announcement.className = 'sr-only';
            announcement.textContent = `Switched to ${tabKey} tab`;
            document.body.appendChild(announcement);

            setTimeout(() => {
                document.body.removeChild(announcement);
            }, 1000);

            // Smooth scroll to content on mobile
            if (window.innerWidth < 768) {
                const contentArea = document.querySelector('.tab-content');
                if (contentArea) {
                    contentArea.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });

        // Enhanced URL hash handling
        document.addEventListener('DOMContentLoaded', function() {
            const hash = window.location.hash.substring(1);
            if (hash) {
                $wire.setActiveTab(hash);
            }
        });

        // Keyboard navigation support
        document.addEventListener('keydown', function(e) {
            if (e.target.matches('[role="tab"], button[wire\\:click*="setActiveTab"]')) {
                const tabs = Array.from(document.querySelectorAll('button[wire\\:click*="setActiveTab"]'));
                const currentIndex = tabs.indexOf(e.target);

                let newIndex;
                if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                    newIndex = (currentIndex + 1) % tabs.length;
                    e.preventDefault();
                } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                    newIndex = (currentIndex - 1 + tabs.length) % tabs.length;
                    e.preventDefault();
                } else if (e.key === 'Home') {
                    newIndex = 0;
                    e.preventDefault();
                } else if (e.key === 'End') {
                    newIndex = tabs.length - 1;
                    e.preventDefault();
                }

                if (newIndex !== undefined) {
                    tabs[newIndex].focus();
                    tabs[newIndex].click();
                }
            }
        });

        // Handle responsive tab scrolling
        function ensureTabVisible(activeTab) {
            const tabsContainer = activeTab.closest('nav');
            if (tabsContainer && tabsContainer.scrollWidth > tabsContainer.clientWidth) {
                const tabRect = activeTab.getBoundingClientRect();
                const containerRect = tabsContainer.getBoundingClientRect();

                if (tabRect.left < containerRect.left) {
                    tabsContainer.scrollLeft -= (containerRect.left - tabRect.left + 20);
                } else if (tabRect.right > containerRect.right) {
                    tabsContainer.scrollLeft += (tabRect.right - containerRect.right + 20);
                }
            }
        }

        // Ensure active tab is visible on mobile
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    const target = mutation.target;
                    if (target.matches('button[wire\\:click*="setActiveTab"]') &&
                        target.classList.contains('border-blue-500')) {
                        ensureTabVisible(target);
                    }
                }
            });
        });

        document.querySelectorAll('button[wire\\:click*="setActiveTab"]').forEach(tab => {
            observer.observe(tab, {
                attributes: true
            });
        });
    </script>
@endscript

@push('styles')
    <style>
        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }

        /* Enhanced focus styles for accessibility */
        @media (prefers-reduced-motion: no-preference) {
            .tab-content {
                animation: fadeIn 0.2s ease-in;
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(4px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Dark mode improvements */
        @media (prefers-color-scheme: dark) {
            .prose {
                color: rgb(229 231 235);
            }

            .prose h1,
            .prose h2,
            .prose h3,
            .prose h4,
            .prose h5,
            .prose h6 {
                color: rgb(243 244 246);
            }
        }
    </style>
@endpush
