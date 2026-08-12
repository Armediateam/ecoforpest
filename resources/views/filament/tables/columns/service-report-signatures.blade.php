@php
    $technicianSignature = $getRecord()->technician_signature;
    $clientSignature = $getRecord()->client_signature;

    $hasTechSignature = !empty($technicianSignature);
    $hasClientSignature = !empty($clientSignature);

    $signatureCount = ($hasTechSignature ? 1 : 0) + ($hasClientSignature ? 1 : 0);

    // Determine status and styling
    if ($signatureCount === 2) {
        $status = 'complete';
        $color = 'success';
        $icon = 'heroicon-s-check-circle';
        $label = 'Signed';
    } elseif ($signatureCount === 1) {
        $status = 'partial';
        $color = 'warning';
        $icon = 'heroicon-s-pencil-square';
        $label = 'Partial';
    } else {
        $status = 'pending';
        $color = 'gray';
        $icon = 'heroicon-s-clock';
        $label = 'Pending';
    }
@endphp

<div class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md"
    title="Tech: {{ $hasTechSignature ? 'Signed' : 'Pending' }}, Client: {{ $hasClientSignature ? 'Signed' : 'Pending' }}">
    {{-- Main signature status --}}
    <x-filament::icon :icon="$icon" @class([
        'h-3 w-3 flex-shrink-0',
        'text-gray-400' => $color === 'gray',
        'text-green-500' => $color === 'success',
        'text-amber-500' => $color === 'warning',
    ]) />

    <span @class([
        'text-xs font-medium leading-none',
        'text-gray-500' => $color === 'gray',
        'text-green-600' => $color === 'success',
        'text-amber-600' => $color === 'warning',
    ])>
        {{ $label }}
    </span>

    {{-- Signature indicators with count --}}
    <div class="flex items-center gap-0.5 ml-1">
        <span @class([
            'text-xs font-medium leading-none',
            'text-gray-400' => $signatureCount === 0,
            'text-amber-500' => $signatureCount === 1,
            'text-green-500' => $signatureCount === 2,
        ])>{{ $signatureCount }}/2</span>

        <div @class([
            'w-1.5 h-1.5 rounded-full border border-gray-300',
            'bg-green-400' => $hasTechSignature,
            'bg-gray-200' => !$hasTechSignature,
        ]) title="Technician {{ $hasTechSignature ? 'Signed' : 'Pending' }}"></div>

        <div @class([
            'w-1.5 h-1.5 rounded-full border border-gray-300',
            'bg-blue-400' => $hasClientSignature,
            'bg-gray-200' => !$hasClientSignature,
        ]) title="Client {{ $hasClientSignature ? 'Signed' : 'Pending' }}"></div>
    </div>
</div>
