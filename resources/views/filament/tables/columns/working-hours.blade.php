@php
$record = $getRecord();
$clockIn = $record->clock_in;
$clockOut = $record->clock_out;

if ($clockIn && $clockOut) {
try {
$clockInTime = \Carbon\Carbon::parse($clockIn);
$clockOutTime = \Carbon\Carbon::parse($clockOut);
$totalMinutes = abs($clockOutTime->diffInMinutes($clockInTime));
$hours = intval($totalMinutes / 60);
$minutes = $totalMinutes % 60;
$formattedTime = $hours . 'h ' . $minutes . 'm';
$badgeColor = $totalMinutes >= 480 ? 'success' : ($totalMinutes >= 240 ? 'warning' : 'danger'); // 8h = 480m, 4h = 240m
} catch (\Exception $e) {
$formattedTime = 'Error';
$badgeColor = 'gray';
$totalMinutes = 0;
}
} else {
$formattedTime = 'Not Set';
$badgeColor = 'gray';
$totalMinutes = 0;
}
@endphp

<div class="flex items-center gap-1">
    <x-filament::badge :color="$badgeColor" size="sm">
        {{ $formattedTime }}
    </x-filament::badge>

    @if ($clockIn && $clockOut && $totalMinutes >= 480)
    <x-heroicon-o-check-circle class="w-4 h-4 text-green-500" />
    @elseif($clockIn && $clockOut && $totalMinutes > 0 && $totalMinutes
    < 240)
        <x-heroicon-o-exclamation-triangle class="w-4 h-4 text-red-500" />
    @endif
</div>