@php
    $record = $getRecord();
    $hasClockIn = !empty($record->image_clock_in);
    $hasClockOut = !empty($record->image_clock_out);
@endphp

<div class="flex items-center gap-2">
    @if ($hasClockIn)
        <div class="flex items-center gap-1">
            <x-heroicon-o-camera class="w-4 h-4 text-green-500" />
            <span class="text-xs text-green-600 font-medium">In</span>
        </div>
    @endif

    @if ($hasClockOut)
        <div class="flex items-center gap-1">
            <x-heroicon-o-camera class="w-4 h-4 text-blue-500" />
            <span class="text-xs text-blue-600 font-medium">Out</span>
        </div>
    @endif

    @if (!$hasClockIn && !$hasClockOut)
        <div class="flex items-center gap-1">
            <x-heroicon-o-x-mark class="w-4 h-4 text-gray-400" />
            <span class="text-xs text-gray-500">No Photos</span>
        </div>
    @endif
</div>
