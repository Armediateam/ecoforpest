<div class="bg-white p-4 rounded-lg">
    @if(isset($error))
        <!-- Show error info if there was a problem with Filament's infolist -->
        <div class="mb-4 rounded-lg border border-yellow-500 bg-yellow-50 p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">Filament Infolist Error</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p>{{ $error }}</p>
                        <p class="mt-2">Using fallback infolist view. Please define a proper <code class="font-mono bg-yellow-100 px-1 rounded">infolist()</code> method in your resource.</p>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="rounded-lg border border-blue-500 bg-blue-50 p-4 mb-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2h-1V9a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">Filament Infolist Available</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <p>This is the legacy infolist view. You can now define infoslists in your Resource class using <code class="font-mono bg-blue-100 px-1 rounded">infolist()</code> method.</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
    
    @php
        $fields = [
            'Basic Information' => [
                ['label' => 'Name', 'field' => 'name'],
                ['label' => 'Email', 'field' => 'email', 'type' => 'email'],
                ['label' => 'Phone', 'field' => 'phone', 'type' => 'phone'],
                ['label' => 'Company', 'field' => 'company'],
            ],
            'Address Information' => [
                ['label' => 'Address', 'field' => 'address'],
                ['label' => 'City', 'field' => 'city'],
                ['label' => 'State', 'field' => 'state'],
                ['label' => 'ZIP Code', 'field' => 'zip_code'],
                ['label' => 'Country', 'field' => 'country.name'],
            ],
            'Additional Information' => [
                ['label' => 'Website', 'field' => 'website', 'type' => 'url'],
                ['label' => 'Position', 'field' => 'position'],
                ['label' => 'Default Language', 'field' => 'default_language'],
            ]
        ];
        
        // Add lead-specific fields if it's a Lead model
        if($record instanceof \App\Models\Lead) {
            $fields['Lead Information'] = [
                ['label' => 'Status', 'field' => 'status.name', 'type' => 'badge'],
                ['label' => 'Source', 'field' => 'source.name', 'type' => 'badge'],
                ['label' => 'Assigned To', 'field' => 'assigned.name'],
                ['label' => 'Lead Value', 'field' => 'lead_value', 'type' => 'currency'],
                ['label' => 'Date Contacted', 'field' => 'date_contacted', 'type' => 'datetime'],
                ['label' => 'Public', 'field' => 'is_public', 'type' => 'boolean'],
            ];
        }
        
        // Add customer-specific fields if it's a Customer model
        if($record instanceof \App\Models\Customer) {
            $fields['Customer Information'] = [
                ['label' => 'Lead', 'field' => 'lead.name'],
                ['label' => 'Customer Group', 'field' => 'customerGroup.name'],
                ['label' => 'Active', 'field' => 'is_active', 'type' => 'boolean'],
            ];
        }
    @endphp

    @foreach($fields as $sectionTitle => $sectionFields)
        @php
            $hasVisibleFields = collect($sectionFields)->some(fn($field) => data_get($record, $field['field']));
        @endphp
        
        @if($hasVisibleFields)
            <div class="mb-8">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4 pb-2 border-b border-gray-200">
                    {{ $sectionTitle }}
                </h3>
                
                <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                    @foreach($sectionFields as $field)
                        @php
                            $value = data_get($record, $field['field']);
                        @endphp
                        
                        @if($value !== null && $value !== '')
                            <div>
                                <dt class="text-sm font-medium text-gray-500">
                                    {{ $field['label'] }}
                                </dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    @switch($field['type'] ?? 'text')
                                        @case('email')
                                            <a href="mailto:{{ $value }}" class="text-blue-600 hover:text-blue-800">
                                                {{ $value }}
                                            </a>
                                            @break
                                            
                                        @case('phone')
                                            <a href="tel:{{ $value }}" class="text-blue-600 hover:text-blue-800">
                                                {{ $value }}
                                            </a>
                                            @break
                                            
                                        @case('url')
                                            <a href="{{ $value }}" target="_blank" class="text-blue-600 hover:text-blue-800">
                                                {{ $value }}
                                                <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="inline w-3 h-3 ml-1" />
                                            </a>
                                            @break
                                            
                                        @case('currency')
                                            <span class="font-medium text-green-600">
                                                {{ number_format($value, 0, ',', '.') }}
                                            </span>
                                            @break
                                            
                                        @case('datetime')
                                            <span title="{{ $value }}">
                                                {{ \Carbon\Carbon::parse($value)->format('M j, Y g:i A') }}
                                            </span>
                                            @break
                                            
                                        @case('boolean')
                                            <span @class([
                                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                                'bg-green-100 text-green-800' => $value,
                                                'bg-red-100 text-red-800' => !$value
                                            ])>
                                                {{ $value ? 'Yes' : 'No' }}
                                            </span>
                                            @break
                                            
                                        @case('badge')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ $value }}
                                            </span>
                                            @break
                                            
                                        @default
                                            {{ $value }}
                                    @endswitch
                                </dd>
                            </div>
                        @endif
                    @endforeach
                </dl>
            </div>
        @endif
    @endforeach
    
    @if(method_exists($record, 'created_at'))
        <div class="mt-8 pt-6 border-t border-gray-200">
            <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Created</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        {{ $record->created_at->format('M j, Y g:i A') }}
                        <span class="text-gray-500">({{ $record->created_at->diffForHumans() }})</span>
                    </dd>
                </div>
                
                @if($record->updated_at && $record->updated_at != $record->created_at)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $record->updated_at->format('M j, Y g:i A') }}
                            <span class="text-gray-500">({{ $record->updated_at->diffForHumans() }})</span>
                        </dd>
                    </div>
                @endif
            </dl>
        </div>
    @endif
</div>
