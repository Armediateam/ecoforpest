@php
$id = $getId();
$statePath = $getStatePath();
@endphp

<x-dynamic-component
    :component="$getEntryWrapperView()"
    :entry="$entry">
    <div
        wire:ignore
        ax-load
        x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('leaflet-map-picker', 'afsakar/filament-leaflet-map-picker'))]"
        ax-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('leaflet-map-picker-entry', 'afsakar/filament-leaflet-map-picker') }}"
        x-data="leafletMapEntry({
            location: {{ json_encode($getState()) }},
            config: {
                defaultZoom: {{ $getDefaultZoom() }},
                defaultLocation: {{ json_encode($getDefaultLocation()) }},
                tileProvider: '{{ $getTileProvider() }}',
                showTileControl: {{ $getShowTileControl() ? 'true' : 'false' }},
                customMarker: {{ $getCustomMarker() ? json_encode($getCustomMarker()) : 'null' }},
                customTiles: {{ json_encode($getCustomTiles()) }},
                markerIconPath: '{{ $getMarkerIconPath() }}',
                markerShadowPath: '{{ $getMarkerShadowPath() }}'
            }
        })"
        x-ignore>
        <div class="relative w-full mx-auto rounded-lg overflow-hidden shadow bg-gray-50 dark:bg-gray-700">
            <div
                x-ref="mapContainer"
                class="leaflet-map-picker w-full relative"
                style="height: {{ $getHeight() }}; z-index: 1;"></div>
        </div>
    </div>
</x-dynamic-component>