@php
    $surveyData = $getRecord()->workOrder->surveyWithAnswers ?? [];
@endphp

<div class="space-y-4">
    @if (!empty($surveyData))
        @foreach ($surveyData as $formIndex => $form)
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <!-- Form Header -->
                <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                    <div class="flex items-center space-x-2">
                        <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center">
                            <span class="text-xs font-medium text-blue-600">{{ $formIndex + 1 }}</span>
                        </div>
                        <h3 class="text-sm font-semibold text-gray-900">{{ $form['form'] }}</h3>
                        <span class="text-xs text-gray-500">({{ count($form['fields']) }} pertanyaan)</span>
                    </div>
                </div>

                <!-- Form Fields -->
                <div class="divide-y divide-gray-100">
                    @foreach ($form['fields'] as $fieldIndex => $field)
                        <div class="p-4">
                            <div class="flex items-start justify-between">
                                <!-- Question Section -->
                                <div class="flex-1 min-w-0 mr-4">
                                    <div class="flex items-center space-x-2 mb-1">
                                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">
                                            {{ $fieldIndex + 1 }}.
                                        </span>
                                        <h4 class="text-sm font-medium text-gray-900">{{ $field['label'] }}</h4>
                                        @if ($field['required'] ?? false)
                                            <span class="text-red-500 text-xs">*</span>
                                        @endif
                                    </div>

                                    <!-- Field Type Badge -->
                                    <div class="flex items-center space-x-2 mb-2">
                                        <span
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                            {{ match ($field['type']) {
                                                'text' => 'bg-blue-100 text-blue-800',
                                                'textarea' => 'bg-green-100 text-green-800',
                                                'number' => 'bg-purple-100 text-purple-800',
                                                'radio', 'checkbox', 'select' => 'bg-orange-100 text-orange-800',
                                                'file' => 'bg-pink-100 text-pink-800',
                                                'signature' => 'bg-indigo-100 text-indigo-800',
                                                default => 'bg-gray-100 text-gray-800',
                                            } }}">
                                            @switch($field['type'])
                                                @case('text')
                                                    📝 Text Input
                                                @break

                                                @case('textarea')
                                                    📄 Text Area
                                                @break

                                                @case('number')
                                                    🔢 Number
                                                @break

                                                @case('radio')
                                                    🔘 Radio Buttons
                                                @break

                                                @case('checkbox')
                                                    ☑️ Checkboxes
                                                @break

                                                @case('select')
                                                    📋 Dropdown
                                                @break

                                                @case('file')
                                                    📎 File Upload {{ $field['multiple'] ? '(Multiple)' : '' }}
                                                @break

                                                @case('signature')
                                                    ✍️ Digital Signature
                                                @break

                                                @default
                                                    ❓ {{ ucfirst($field['type']) }}
                                            @endswitch
                                        </span>
                                    </div>

                                    <!-- Field Options (if available) -->
                                    @if (!empty($field['options']))
                                        <div class="mb-2">
                                            <span class="text-xs text-gray-500">Opsi:</span>
                                            <div class="flex flex-wrap gap-1 mt-1">
                                                @if (is_array($field['options']))
                                                    @foreach ($field['options'] as $option)
                                                        <span
                                                            class="inline-flex px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded">
                                                            {{ is_array($option) ? $option['label'] ?? ($option['value'] ?? 'Unknown') : $option }}
                                                        </span>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <!-- Answer Section -->
                                <div class="flex-shrink-0 text-right max-w-xs">
                                    <div class="text-xs text-gray-500 mb-1">Jawaban:</div>
                                    @if ($field['answer'] !== null && $field['answer'] !== '')
                                        @if ($field['type'] === 'file')
                                            @if (is_array($field['answer']))
                                                <div class="space-y-1">
                                                    @foreach ($field['answer'] as $file)
                                                        <a href="{{ $file['url'] ?? '#' }}" target="_blank"
                                                            class="flex items-center space-x-1 bg-green-50 p-2 rounded text-xs hover:bg-green-100 transition-colors">
                                                            <svg class="w-3 h-3 text-green-600" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                                                </path>
                                                            </svg>
                                                            <span
                                                                class="text-green-800 truncate">{{ basename($file['path'] ?? '') }}</span>
                                                            <svg class="w-3 h-3 text-green-600" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                                            </svg>
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="text-xs text-gray-500 italic">Format file tidak valid</div>
                                            @endif
                                        @elseif($field['type'] === 'signature')
                                            @if ($field['answer'])
                                                <img src="{{ $field['original_answer'] }}" alt="Signature"
                                                    class="w-24 h-12 object-contain border border-gray-200 rounded" />
                                            @else
                                                <div class="text-xs text-gray-500 italic">Belum ditandatangani</div>
                                            @endif
                                        @elseif(is_array($field['answer']))
                                            <div class="space-y-1">
                                                @foreach ($field['answer'] as $answer)
                                                    <span
                                                        class="inline-block bg-blue-50 text-blue-800 px-2 py-1 rounded text-xs">
                                                        {{ $answer }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="bg-blue-50 p-2 rounded">
                                                <span
                                                    class="text-sm text-blue-900 break-words">{{ $field['answer'] }}</span>
                                            </div>
                                        @endif
                                    @else
                                        <div class="bg-gray-50 p-2 rounded">
                                            <span class="text-xs text-gray-500 italic">Belum dijawab</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    @else
        <div class="text-center py-8">
            <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                </path>
            </svg>
            <h3 class="text-sm font-medium text-gray-900 mb-1">Tidak ada data survey</h3>
            <p class="text-xs text-gray-500">Data surveyWithAnswers tidak tersedia untuk work order ini</p>
        </div>
    @endif
</div>
