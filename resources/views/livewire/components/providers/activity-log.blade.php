<div class="w-full bg-transparent">
    <div class="activity-timeline bg-transparent dark:bg-transparent">
        @if ($activities->isNotEmpty())
            @foreach ($activities as $activity)
                <div class="timeline-entry {{ $activity->event }}">
                    <div @class([
                        'activity-icon',
                        'bg-green-500' => $activity->event === 'created',
                        'bg-blue-500' => $activity->event === 'updated',
                        'bg-red-500' => $activity->event === 'deleted',
                        'bg-yellow-500' => $activity->event === 'restored',
                        'bg-purple-500' => $activity->event === 'converted',
                        'bg-orange-500' => $activity->event === 'status_changed',
                        'bg-indigo-500' => $activity->event === 'assigned',
                        'bg-cyan-500' => $activity->event === 'commented',
                        'bg-gray-500' => !in_array($activity->event, [
                            'created',
                            'updated',
                            'deleted',
                            'restored',
                            'converted',
                            'status_changed',
                            'assigned',
                            'commented',
                        ]),
                    ])>
                        @switch($activity->event)
                            @case('created')
                                <x-filament::icon icon="heroicon-o-plus" class="w-3 h-3 text-white" />
                            @break

                            @case('updated')
                                <x-filament::icon icon="heroicon-o-pencil" class="w-3 h-3 text-white" />
                            @break

                            @case('deleted')
                                <x-filament::icon icon="heroicon-o-trash" class="w-3 h-3 text-white" />
                            @break

                            @case('restored')
                                <x-filament::icon icon="heroicon-o-arrow-path" class="w-3 h-3 text-white" />
                            @break

                            @case('converted')
                                <x-filament::icon icon="heroicon-o-user-plus" class="w-3 h-3 text-white" />
                            @break

                            @case('status_changed')
                                <x-filament::icon icon="heroicon-o-arrows-right-left" class="w-3 h-3 text-white" />
                            @break

                            @case('assigned')
                                <x-filament::icon icon="heroicon-o-user-circle" class="w-3 h-3 text-white" />
                            @break

                            @case('commented')
                                <x-filament::icon icon="heroicon-o-chat-bubble-left-ellipsis" class="w-3 h-3 text-white" />
                            @break

                            @default
                                <x-filament::icon icon="heroicon-o-clock" class="w-3 h-3 text-white" />
                        @endswitch
                    </div>

                    <!-- Timeline Card -->
                    <div
                        class="timeline-card bg-slate-50 dark:bg-slate-800/50 border border-gray-200 dark:border-slate-600 shadow-sm dark:shadow-slate-900/20 hover:shadow-md dark:hover:shadow-slate-900/40">
                        <!-- Status Badge -->
                        <div class="status-badge">
                            <span @class([
                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' =>
                                    $activity->event === 'created',
                                'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' =>
                                    $activity->event === 'updated',
                                'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' =>
                                    $activity->event === 'deleted',
                                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' =>
                                    $activity->event === 'restored',
                                'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' =>
                                    $activity->event === 'converted',
                                'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200' =>
                                    $activity->event === 'status_changed',
                                'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200' =>
                                    $activity->event === 'assigned',
                                'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200' =>
                                    $activity->event === 'commented',
                                'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' => !in_array(
                                    $activity->event,
                                    [
                                        'created',
                                        'updated',
                                        'deleted',
                                        'restored',
                                        'converted',
                                        'status_changed',
                                        'assigned',
                                        'commented',
                                    ]),
                            ])>
                                {{ ucfirst($activity->event) }}
                            </span>
                        </div>

                        <!-- Activity Header -->
                        <div class="mb-3">
                            @php
                                $modelType = match (class_basename($activity->subject_type)) {
                                    'Lead' => 'Lead',
                                    'Customer' => 'Customer',
                                    'Proposal' => 'Proposal',
                                    'Contract' => 'Kontrak',
                                    default => class_basename($activity->subject_type),
                                };

                                $subjectName =
                                    $activity->subject->name ??
                                    ($activity->subject->subject ?? ($activity->subject->id ?? ''));

                                // Get status name if status_id changed
                                $statusName = '';
                                if (
                                    $activity->event === 'status_changed' &&
                                    isset($activity->properties['attributes']['status_id'])
                                ) {
                                    $status = \App\Models\Status::find(
                                        $activity->properties['attributes']['status_id'],
                                    );
                                    $statusName = $status ? $status->name : '';
                                }

                                // Get assigned user name if assigned_id changed
                                $assignedName = '';
                                if (
                                    $activity->event === 'assigned' &&
                                    isset($activity->properties['attributes']['assigned_id'])
                                ) {
                                    $user = \App\Models\User::find($activity->properties['attributes']['assigned_id']);
                                    $assignedName = $user ? $user->name : '';
                                }

                                $descriptions = [
                                    'created' => "membuat {$modelType} baru",
                                    'updated' => "memperbarui {$modelType}",
                                    'deleted' => "menghapus {$modelType}",
                                    'restored' => "memulihkan {$modelType}",
                                    'converted' => "mengkonversi {$modelType} menjadi customer",
                                    'status_changed' => "mengubah status {$modelType} menjadi '{$statusName}'",
                                    'assigned' => "menugaskan {$modelType} kepada {$assignedName}",
                                    'commented' => "menambahkan komentar pada {$modelType}",
                                ];
                                $actionDescription = $descriptions[$activity->event] ?? $activity->event;
                            @endphp

                            <div class="flex items-start justify-between">
                                <div class="flex-1 pr-4">
                                    <h4
                                        class="text-sm font-semibold text-gray-900 dark:text-slate-100 leading-tight mb-1">
                                        <span
                                            class="text-primary-600 dark:text-primary-400">{{ $activity->causer?->name ?? 'System' }}</span>
                                        {{ $actionDescription }}
                                        @if ($subjectName)
                                            <span class="font-normal text-gray-700 dark:text-slate-300">
                                                "{{ Str::limit($subjectName, 30) }}"
                                            </span>
                                        @endif
                                    </h4>

                                    <div class="flex items-center space-x-2 text-xs text-gray-500 dark:text-slate-400">
                                        <x-filament::icon icon="heroicon-o-clock" class="w-3 h-3" />
                                        <span>{{ $activity->created_at->locale('id')->isoFormat('dddd, D MMMM YYYY HH:mm') }}</span>
                                        <span>•</span>
                                        <span>{{ $activity->created_at->locale('id')->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Activity Details -->
                        @if ($activity->properties && $activity->properties->isNotEmpty())
                            @if ($activity->properties->has('attributes') && $activity->properties->has('old'))
                                <div class="border-t border-slate-100 dark:border-slate-700 pt-3 mt-3">
                                    <details class="group">
                                        <summary
                                            class="cursor-pointer flex items-center text-xs text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 transition-colors focus:outline-none">
                                            <x-filament::icon icon="heroicon-o-chevron-right"
                                                class="w-3 h-3 mr-1 transition-transform group-open:rotate-90" />
                                            Lihat Detail Perubahan
                                        </summary>
                                        <div
                                            class="mt-2 bg-slate-50 dark:bg-slate-800/50 p-3 rounded-lg border border-gray-200 dark:border-slate-600">
                                            <div class="space-y-2">
                                                @foreach ($activity->properties['attributes'] as $key => $newValue)
                                                    @if (isset($activity->properties['old'][$key]) && $activity->properties['old'][$key] != $newValue)
                                                        <div class="text-xs">
                                                            @php
                                                                $fieldNames = [
                                                                    'name' => 'Nama',
                                                                    'email' => 'Email',
                                                                    'phone' => 'Telepon',
                                                                    'status_id' => 'Status',
                                                                    'assigned_id' => 'Ditugaskan kepada',
                                                                    'source_id' => 'Sumber',
                                                                    'description' => 'Deskripsi',
                                                                    'date_contacted' => 'Tanggal Kontak',
                                                                    'lead_value' => 'Nilai Lead',
                                                                    'is_public' => 'Status Publik',
                                                                    'customer_id' => 'Customer',
                                                                    'lead_id' => 'Lead',
                                                                    'address' => 'Alamat',
                                                                    'city' => 'Kota',
                                                                    'state' => 'Provinsi',
                                                                    'zip_code' => 'Kode Pos',
                                                                    'country_id' => 'Negara',
                                                                    'company_name' => 'Nama Perusahaan',
                                                                    'position' => 'Jabatan',
                                                                    'website' => 'Website',
                                                                    'notes' => 'Catatan',
                                                                ];

                                                                // Get related model names if needed
                                                                if ($key === 'status_id' && $newValue) {
                                                                    $status = \App\Models\Status::find($newValue);
                                                                    $newValue = $status ? $status->name : $newValue;
                                                                    $oldValue =
                                                                        \App\Models\Status::find(
                                                                            $activity->properties['old'][$key],
                                                                        )?->name ?? $activity->properties['old'][$key];
                                                                } elseif ($key === 'assigned_id' && $newValue) {
                                                                    $user = \App\Models\User::find($newValue);
                                                                    $newValue = $user ? $user->name : $newValue;
                                                                    $oldValue =
                                                                        \App\Models\User::find(
                                                                            $activity->properties['old'][$key],
                                                                        )?->name ?? $activity->properties['old'][$key];
                                                                } elseif ($key === 'source_id' && $newValue) {
                                                                    $source = \App\Models\Source::find($newValue);
                                                                    $newValue = $source ? $source->name : $newValue;
                                                                    $oldValue =
                                                                        \App\Models\Source::find(
                                                                            $activity->properties['old'][$key],
                                                                        )?->name ?? $activity->properties['old'][$key];
                                                                } else {
                                                                    $oldValue = $activity->properties['old'][$key];
                                                                }
                                                                $fieldName =
                                                                    $fieldNames[$key] ??
                                                                    ucwords(str_replace('_', ' ', $key));
                                                            @endphp

                                                            <div class="flex items-center justify-between">
                                                                <span
                                                                    class="font-medium text-gray-700 dark:text-slate-300">{{ $fieldName }}:</span>
                                                                <div class="flex items-center space-x-1">
                                                                    <span
                                                                        class="px-2 py-1 bg-red-50 dark:bg-red-900/50 text-red-700 dark:text-red-300 rounded text-xs border border-red-200 dark:border-red-700">
                                                                        {{ is_array($oldValue) ? json_encode($oldValue) : (is_bool($oldValue) ? ($oldValue ? 'Ya' : 'Tidak') : ($oldValue ?: 'Kosong')) }}
                                                                    </span>
                                                                    <x-filament::icon icon="heroicon-o-arrow-right"
                                                                        class="w-3 h-3 text-gray-400 dark:text-slate-500" />
                                                                    <span
                                                                        class="px-2 py-1 bg-green-50 dark:bg-green-900/50 text-green-700 dark:text-green-300 rounded text-xs border border-green-200 dark:border-green-700">
                                                                        {{ is_array($newValue) ? json_encode($newValue) : (is_bool($newValue) ? ($newValue ? 'Ya' : 'Tidak') : ($newValue ?: 'Kosong')) }}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    </details>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach

            @if ($activities->count() >= ($config['limit'] ?? 20))
                <div class="timeline-entry default">
                    <div class="activity-icon bg-gray-400 dark:bg-gray-600">
                        <x-filament::icon icon="heroicon-o-ellipsis-horizontal" class="w-3 h-3 text-white" />
                    </div>
                    <div
                        class="timeline-card bg-slate-50 dark:bg-slate-800/50 border-gray-200 dark:border-slate-600 border-dashed">
                        <div class="text-center py-4">
                            <x-filament::button wire:click="$dispatch('loadMoreActivities')" size="sm"
                                color="gray" class="inline-flex items-center">
                                <x-filament::icon icon="heroicon-o-arrow-down" class="w-4 h-4 mr-2" />
                                Muat Lebih Banyak
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            @endif
        @else
            <div class="timeline-entry default">
                <div class="activity-icon bg-gray-400 dark:bg-gray-600">
                    <x-filament::icon icon="heroicon-o-document-text" class="w-3 h-3 text-white" />
                </div>
                <div class="timeline-card bg-slate-50 dark:bg-slate-800 border-gray-200 dark:border-slate-600">
                    <div class="text-center py-8">
                        <div
                            class="mx-auto w-12 h-12 bg-gray-100 dark:bg-slate-600 rounded-full flex items-center justify-center mb-4">
                            <x-filament::icon icon="heroicon-o-document-text"
                                class="w-6 h-6 text-gray-400 dark:text-slate-400" />
                        </div>
                        <h3 class="text-sm font-medium text-gray-900 dark:text-slate-100 mb-2">Belum Ada Aktivitas</h3>
                        <p class="text-xs text-gray-500 dark:text-slate-400 max-w-sm mx-auto leading-relaxed">
                            Aktivitas akan muncul di sini ketika ada perubahan atau tindakan yang dilakukan pada
                            {{ strtolower(class_basename($record)) }} ini.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
