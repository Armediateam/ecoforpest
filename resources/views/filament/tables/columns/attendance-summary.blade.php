<div class="px-3 py-2">
    <div class="flex items-center justify-center space-x-1 text-xs">
        <!-- Work Days -->
        <div class="flex items-center space-x-1">
            <span
                class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full 
                {{ $getRecord()->work_days >= 20 ? 'bg-green-100 text-green-800' : ($getRecord()->work_days >= 15 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                ✅ {{ $getRecord()->work_days }}
            </span>
        </div>

        <!-- Leave Days -->
        @if ($getRecord()->leave_days > 0)
            <div class="flex items-center space-x-1">
                <span
                    class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full 
                {{ $getRecord()->leave_days > 3 ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800' }}">
                    🏖️ {{ $getRecord()->leave_days }}
                </span>
            </div>
        @endif

        <!-- Permission Days -->
        @if ($getRecord()->permission_days > 0)
            <div class="flex items-center space-x-1">
                <span
                    class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                    📝 {{ $getRecord()->permission_days }}
                </span>
            </div>
        @endif

        <!-- Absent Days -->
        @if ($getRecord()->absent_days > 0)
            <div class="flex items-center space-x-1">
                <span
                    class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
                    ❌ {{ $getRecord()->absent_days }}
                </span>
            </div>
        @endif

        <!-- Overtime -->
        @if ($getRecord()->overtime_hours > 0)
            <div class="flex items-center space-x-1">
                <span
                    class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-800">
                    ⏰ {{ $getRecord()->overtime_hours }}h
                </span>
            </div>
        @endif
    </div>

    <!-- Summary tooltip on hover -->
    <div class="mt-1 text-center">
        <span class="text-xs text-gray-500"
            title="Total days: {{ $getRecord()->work_days + $getRecord()->leave_days + $getRecord()->permission_days + $getRecord()->absent_days }}">
            Working Days:
            {{ $getRecord()->work_days + $getRecord()->leave_days + $getRecord()->permission_days + $getRecord()->absent_days }}
            days
        </span>
    </div>
</div>
