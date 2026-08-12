@php
    $technicianApproved = $getRecord()->technician_approve;
    $clientApproved = $getRecord()->client_approve;

    if ($technicianApproved && $clientApproved) {
        $status = 'both_approved';
        $color = 'success';
        $icon = 'heroicon-s-check-circle';
        $label = 'Approved';
    } elseif ($technicianApproved && !$clientApproved) {
        $status = 'technician_only';
        $color = 'warning';
        $icon = 'heroicon-s-user-circle';
        $label = 'Tech Only';
    } elseif (!$technicianApproved && $clientApproved) {
        $status = 'client_only';
        $color = 'info';
        $icon = 'heroicon-s-identification';
        $label = 'Client Only';
    } else {
        $status = 'pending';
        $color = 'gray';
        $icon = 'heroicon-s-clock';
        $label = 'Pending';
    }
@endphp

<div class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md"
    title="Tech: {{ $technicianApproved ? 'Approved' : 'Pending' }}, Client: {{ $clientApproved ? 'Approved' : 'Pending' }}">
    {{-- Main status indicator --}}
    <x-filament::icon :icon="$icon" @class([
        'h-3 w-3 flex-shrink-0',
        'text-gray-400' => $color === 'gray',
        'text-green-500' => $color === 'success',
        'text-amber-500' => $color === 'warning',
        'text-blue-500' => $color === 'info',
    ]) />

    <span @class([
        'text-xs font-medium leading-none',
        'text-gray-500' => $color === 'gray',
        'text-green-600' => $color === 'success',
        'text-amber-600' => $color === 'warning',
        'text-blue-600' => $color === 'info',
    ])>
        {{ $label }}
    </span>

    {{-- Compact approval indicators --}}
    <div class="flex items-center gap-0.5 ml-1">
        <div @class([
            'w-1.5 h-1.5 rounded-full border border-gray-300',
            'bg-green-400' => $technicianApproved,
            'bg-gray-200' => !$technicianApproved,
        ]) title="Technician {{ $technicianApproved ? 'Approved' : 'Pending' }}"></div>

        <div @class([
            'w-1.5 h-1.5 rounded-full border border-gray-300',
            'bg-blue-400' => $clientApproved,
            'bg-gray-200' => !$clientApproved,
        ]) title="Client {{ $clientApproved ? 'Approved' : 'Pending' }}"></div>
    </div>
</div>
