<x-filament-panels::page>
    @push('styles')
        <link href='https://api.mapbox.com/mapbox-gl-js/v3.0.1/mapbox-gl.css' rel='stylesheet' />
        <style>
            .mapboxgl-popup-content {
                padding: 0;
                border-radius: 8px;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
                transition: opacity 0.2s ease-in-out;
                background: white;
            }

            .dark .mapboxgl-popup-content {
                background: rgb(15 23 42);
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
                border: 1px solid rgb(30 41 59);
            }

            .hover-popup .mapboxgl-popup-content {
                pointer-events: none;
            }

            .hover-popup .mapboxgl-popup-tip {
                pointer-events: none;
            }

            /* Hide Mapbox watermark/attribution */
            .mapboxgl-ctrl-attrib {
                display: none !important;
            }

            .mapboxgl-ctrl-logo {
                display: none !important;
            }

            /* Fixed floating info panel */
            .floating-info-panel {
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: white;
                border-radius: 12px;
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
                padding: 12px 16px;
                z-index: 1000;
                min-width: 280px;
                max-width: 320px;
                opacity: 0;
                transform: translateY(10px);
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                pointer-events: none;
                border: 1px solid #e5e7eb;
                backdrop-filter: blur(10px);
                background: rgba(255, 255, 255, 0.98);
            }

            .dark .floating-info-panel {
                background: rgba(15, 23, 42, 0.98);
                border: 1px solid rgb(30 41 59);
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
            }

            .floating-info-panel.show {
                opacity: 1;
                transform: translateY(0);
            }

            .floating-info-header {
                display: flex;
                align-items: center;
                margin-bottom: 8px;
                padding-bottom: 8px;
                border-bottom: 1px solid #f3f4f6;
            }

            .dark .floating-info-header {
                border-bottom: 1px solid rgb(30 41 59);
            }

            .floating-info-avatar {
                width: 32px;
                height: 32px;
                border-radius: 50%;
                background: #3b82f6;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-weight: 600;
                font-size: 14px;
                margin-right: 10px;
            }

            .floating-info-name {
                font-weight: 600;
                color: #111827;
                font-size: 14px;
                margin: 0;
            }

            .dark .floating-info-name {
                color: rgb(248 250 252);
            }

            .floating-info-status {
                font-size: 11px;
                margin: 0;
                margin-top: 2px;
            }

            .floating-info-content {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 6px;
                font-size: 11px;
            }

            .floating-info-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 2px 0;
            }

            .floating-info-label {
                color: #6b7280;
                font-weight: 500;
            }

            .dark .floating-info-label {
                color: rgb(148 163 184);
            }

            .floating-info-value {
                color: #111827;
                font-weight: 600;
                text-align: right;
            }

            .dark .floating-info-value {
                color: rgb(248 250 252);
            }

            .floating-info-item.full-width {
                grid-column: 1 / -1;
            }

            .employee-popup {
                padding: 16px;
                min-width: 250px;
                background: white;
            }

            .dark .employee-popup {
                background: rgb(15 23 42);
                color: rgb(248 250 252);
            }

            .employee-card {
                transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
                cursor: pointer;
                background: white;
                border: 1px solid #e5e7eb;
            }

            .dark .employee-card {
                background: rgb(15 23 42);
                border: 1px solid rgb(30 41 59);
            }

            .employee-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            }

            .dark .employee-card:hover {
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            }

            .employee-card.active {
                border-color: #3b82f6;
                background-color: #eff6ff;
            }

            .dark .employee-card.active {
                border-color: #3b82f6;
                background-color: rgb(30 58 138);
            }

            /* Smooth animations for markers */
            .mapboxgl-marker {
                transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            }

            /* Custom marker styles */
            .custom-marker {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                border: 3px solid #fff;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 12px;
                font-weight: bold;
                color: white;
                cursor: pointer;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                position: relative;
            }

            .custom-marker:hover {
                transform: scale(1.1);
                z-index: 1000;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
            }

            .marker-online {
                background-color: #10b981;
                animation: pulse-green 2s infinite;
            }

            .marker-online::after {
                content: '';
                position: absolute;
                top: -3px;
                right: -3px;
                width: 12px;
                height: 12px;
                background-color: #22c55e;
                border: 2px solid white;
                border-radius: 50%;
                animation: blink 1.5s ease-in-out infinite alternate;
            }

            .marker-offline {
                background-color: #ef4444;
                opacity: 0.7;
            }

            .marker-offline::after {
                content: '';
                position: absolute;
                top: -3px;
                right: -3px;
                width: 12px;
                height: 12px;
                background-color: #dc2626;
                border: 2px solid white;
                border-radius: 50%;
            }

            .marker-mocked {
                background-color: #f59e0b;
                border: 3px solid #fbbf24;
                box-shadow: 0 0 10px rgba(245, 158, 11, 0.5);
                animation: pulse-warning 2s infinite;
            }

            .marker-mocked::after {
                content: '⚠';
                position: absolute;
                top: -8px;
                right: -8px;
                width: 16px;
                height: 16px;
                background-color: #dc2626;
                color: white;
                border: 2px solid white;
                border-radius: 50%;
                font-size: 10px;
                font-weight: bold;
                display: flex;
                align-items: center;
                justify-content: center;
                line-height: 1;
            }

            @keyframes pulse-warning {
                0% {
                    box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7);
                }

                70% {
                    box-shadow: 0 0 0 15px rgba(245, 158, 11, 0);
                }

                100% {
                    box-shadow: 0 0 0 0 rgba(245, 158, 11, 0);
                }
            }

            @keyframes pulse-green {
                0% {
                    box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
                }

                70% {
                    box-shadow: 0 0 0 10px rgba(16, 185, 129, 0);
                }

                100% {
                    box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
                }
            }

            @keyframes blink {
                0% {
                    opacity: 1;
                }

                100% {
                    opacity: 0.3;
                }
            }

            /* Loading animations */
            .loading-dots::after {
                content: '';
                animation: loading-dots 1.5s infinite;
            }

            @keyframes loading-dots {

                0%,
                20% {
                    content: '.';
                }

                40% {
                    content: '..';
                }

                60%,
                100% {
                    content: '...';
                }
            }

            /* Smooth fade transitions */
            .fade-in {
                animation: fadeIn 0.5s ease-in;
            }

            .fade-out {
                animation: fadeOut 0.3s ease-out;
            }

            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            @keyframes fadeOut {
                from {
                    opacity: 1;
                    transform: translateY(0);
                }

                to {
                    opacity: 0;
                    transform: translateY(-10px);
                }
            }
        </style>
    @endpush

    @push('scripts')
        <script src='https://api.mapbox.com/mapbox-gl-js/v3.0.1/mapbox-gl.js'></script>
    @endpush <div class="space-y-6">
        <!-- Header dengan form filter -->
        <div class="bg-white dark:bg-slate-950 rounded-lg shadow border border-slate-200 dark:border-slate-900 p-6">
            {{ $this->form }}
        </div>

        <!-- Map Container dan Employee List bersebelahan -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Employee List Panel -->
            <div
                class="lg:col-span-1 bg-white dark:bg-slate-950 rounded-lg shadow border border-slate-200 dark:border-slate-900">
                <div class="p-4 border-b bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100">Active Employees</h3>
                </div>
                <div>
                    <div id="employees-list"
                        class="space-y-3 p-4 h-[600px] overflow-y-auto bg-slate-50 dark:bg-slate-950">
                        <!-- Employee cards will be populated here -->
                    </div>
                </div>
            </div>
            <!-- Map Container -->
            <div
                class="lg:col-span-2 bg-white dark:bg-slate-950 rounded-lg shadow border border-slate-200 dark:border-slate-900 overflow-hidden">
                <div class="p-4 border-b bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="flex items-center space-x-2">
                                <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                                <span class="text-sm font-medium text-gray-700 dark:text-slate-300">Live Tracking</span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="text-sm">
                                <span class="text-gray-500 dark:text-slate-400">Total Employees:</span>
                                <span class="font-semibold text-gray-900 dark:text-slate-100"
                                    id="total-employees">0</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="map" class="w-full h-[600px] relative -m-1">
                    <div id="loading-overlay"
                        class="absolute inset-0 bg-gray-100 dark:bg-slate-900 flex items-center justify-center z-10">
                        <div class="flex flex-col items-center space-y-3">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                            <span class="text-sm text-gray-600 dark:text-slate-300">Loading map...</span>
                        </div>
                    </div>

                    <!-- Floating Info Panel -->
                    <div id="floating-info-panel" class="floating-info-panel">
                        <div class="floating-info-header">
                            <div id="floating-avatar" class="floating-info-avatar">?</div>
                            <div>
                                <h4 id="floating-name" class="floating-info-name">Employee Name</h4>
                                <p id="floating-status" class="floating-info-status">Status</p>
                            </div>
                        </div>
                        <div class="floating-info-content">
                            <div class="floating-info-item full-width" id="floating-mocked-warning"
                                style="display: none;">
                                <div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 rounded text-xs">
                                    <strong>⚠ Warning:</strong> Fake GPS detected!
                                </div>
                            </div>
                            <div class="floating-info-item">
                                <span class="floating-info-label">Department</span>
                                <span id="floating-department" class="floating-info-value">-</span>
                            </div>
                            <div class="floating-info-item">
                                <span class="floating-info-label">Position</span>
                                <span id="floating-position" class="floating-info-value">-</span>
                            </div>
                            <div class="floating-info-item">
                                <span class="floating-info-label">Speed</span>
                                <span id="floating-speed" class="floating-info-value">-</span>
                            </div>
                            <div class="floating-info-item">
                                <span class="floating-info-label">Heading</span>
                                <span id="floating-heading" class="floating-info-value">-</span>
                            </div>
                            <div class="floating-info-item">
                                <span class="floating-info-label">Accuracy</span>
                                <span id="floating-accuracy" class="floating-info-value">-</span>
                            </div>
                            <div class="floating-info-item">
                                <span class="floating-info-label">Altitude</span>
                                <span id="floating-altitude" class="floating-info-value">-</span>
                            </div>
                            <div class="floating-info-item full-width">
                                <span class="floating-info-label">Last Activity</span>
                                <span id="floating-last-update" class="floating-info-value">-</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            // Global variables
            let map;
            let markers = [];
            let currentLocations = [];
            let updateInterval;
            let isUpdating = false;
            let lastUpdateTimestamp = 0;
            let employeeMarkers = new Map(); // Track markers by employee ID

            // MapBox access token
            mapboxgl.accessToken = '{{ $this->getMapboxKey() }}';

            // Initialize map when page loads
            document.addEventListener('DOMContentLoaded', function() {
                initializeMap();
                startRealTimeUpdates();
            });

            function initializeMap() {
                // Default center (Indonesia)
                const defaultCenter = [106.8456, -6.2088]; // Jakarta

                map = new mapboxgl.Map({
                    container: 'map',
                    style: 'mapbox://styles/mapbox/streets-v12',
                    center: defaultCenter,
                    zoom: 10,
                    attributionControl: false
                });

                map.addControl(new mapboxgl.NavigationControl());
                map.addControl(new mapboxgl.FullscreenControl());

                map.on('load', function() {
                    document.getElementById('loading-overlay').style.display = 'none';
                    loadInitialData();
                });
            }

            function startRealTimeUpdates() {
                // Update every 3 seconds for smoother real-time experience
                updateInterval = setInterval(() => {
                    if (!isUpdating) {
                        updateLocationsQuietly();
                    }
                }, 3000); // Reduced from 5000 to 3000ms

                // Also update when user changes filters
                const form = document.querySelector('form');
                if (form) {
                    form.addEventListener('change', function() {
                        // Small delay to let form update
                        setTimeout(() => {
                            updateLocationsQuietly();
                        }, 200);
                    });
                }
            }

            function updateLocationsQuietly() {
                isUpdating = true;

                // Get filter values
                const formData = new FormData();
                const departmentSelect = document.querySelector('select[name="data.department_id"]');
                const positionSelect = document.querySelector('select[name="data.position_id"]');
                const dateInput = document.querySelector('input[name="data.date"]');

                if (departmentSelect && departmentSelect.value) {
                    formData.append('department_id', departmentSelect.value);
                }
                if (positionSelect && positionSelect.value) {
                    formData.append('position_id', positionSelect.value);
                }
                if (dateInput && dateInput.value) {
                    formData.append('date', dateInput.value);
                }

                // Call the dedicated AJAX endpoint
                fetch('/filament/live-locations', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    }).then(data => {
                        if (data.locations) {
                            console.log('AJAX update received', data.locations.length, 'locations'); // Debug log
                            lastUpdateTimestamp = Date.now();
                            updateMapAndList(data.locations);
                            updateTimestamp();

                            // Add subtle visual feedback for successful update
                            const indicator = document.querySelector('.animate-pulse');
                            if (indicator) {
                                indicator.style.backgroundColor = '#22c55e';
                                setTimeout(() => {
                                    indicator.style.backgroundColor = '#10b981';
                                }, 200);
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error updating locations:', error);
                        // Fallback to initial data if API fails
                        updateMapAndList(@json($this->getLocationData()));
                    })
                    .finally(() => {
                        isUpdating = false;
                    });
            }

            function loadInitialData() {
                const locations = @json($this->getLocationData());
                console.log('Initial location data:', locations); // Debug log
                console.log('Loading initial data with', locations.length, 'locations'); // Debug log
                updateMapAndList(locations);
                updateTimestamp();
            }

            function updateMapAndList(locations) {
                currentLocations = locations;

                // Update total count with smooth counter animation
                animateCounter(document.getElementById('total-employees'), locations.length);

                if (locations.length === 0) {
                    updateEmployeesList([]);
                    // Clear all markers
                    employeeMarkers.forEach(marker => marker.remove());
                    employeeMarkers.clear();
                    markers = [];
                    return;
                }

                // Track which employees are still active
                const activeEmployeeIds = new Set(locations.map(loc => loc.employee_id));

                // Remove markers for employees no longer in the list
                employeeMarkers.forEach((marker, employeeId) => {
                    if (!activeEmployeeIds.has(employeeId)) {
                        marker.remove();
                        employeeMarkers.delete(employeeId);
                    }
                });

                // Update markers array
                markers = Array.from(employeeMarkers.values());

                const bounds = new mapboxgl.LngLatBounds();
                let boundsUpdated = false;

                locations.forEach((location) => {
                    const existingMarker = employeeMarkers.get(location.employee_id);

                    if (existingMarker) {
                        // Update existing marker position smoothly
                        const currentLngLat = existingMarker.getLngLat();
                        const newLngLat = [location.longitude, location.latitude];

                        // Only update if position changed significantly (avoid jitter)
                        const distance = getDistance(
                            currentLngLat.lat, currentLngLat.lng,
                            location.latitude, location.longitude
                        );

                        if (distance > 0.001) { // ~100 meters threshold
                            animateMarkerToPosition(existingMarker, newLngLat);
                        } // Update marker appearance based on recent activity
                        updateMarkerStatus(existingMarker, location);

                        // Ensure click event is attached to existing marker
                        addMarkerClickEvent(existingMarker, location);

                        // Ensure click event is attached to existing marker
                        addMarkerClickEvent(existingMarker, location);
                    } else {
                        // Create new marker for new employee
                        createNewMarker(location);
                    }

                    bounds.extend([location.longitude, location.latitude]);
                    boundsUpdated = true;
                });

                // Fit map to show all markers (only on initial load or when filter changes)
                if (boundsUpdated && locations.length > 0 && shouldUpdateBounds()) {
                    map.fitBounds(bounds, {
                        padding: 50,
                        maxZoom: 15,
                        duration: 1500
                    });
                } // Update employees list
                updateEmployeesList(locations);

                console.log('Map and list updated with', locations.length, 'locations'); // Debug log
            }

            function createNewMarker(location) {
                // Create custom marker element
                const markerEl = document.createElement('div');
                markerEl.className = `custom-marker ${getMarkerStatusClass(location)}`;
                markerEl.innerHTML = location.employee_name.charAt(0).toUpperCase();
                markerEl.title = location.employee_name;

                // Create marker with entrance animation
                const marker = new mapboxgl.Marker(markerEl)
                    .setLngLat([location.longitude, location.latitude])
                    .addTo(map);

                // Entrance animation
                markerEl.style.transform = 'scale(0)';
                markerEl.style.transition = 'transform 0.3s ease-out';
                setTimeout(() => {
                    markerEl.style.transform = 'scale(1)';
                }, 100);

                // Add event listeners to marker
                addMarkerEventListeners(marker, location);

                employeeMarkers.set(location.employee_id, marker);
                markers.push(marker);
            }

            function addMarkerEventListeners(marker, location) {
                const markerEl = marker.getElement(); // Add hover events for floating panel
                markerEl.addEventListener('mouseenter', () => {
                    // Get current/updated location data instead of using old closure data
                    const currentLocation = currentLocations.find(loc => loc.employee_id === location.employee_id);
                    console.log('Marker hover - looking for employee_id:', location.employee_id, 'found:', !!
                        currentLocation);
                    if (currentLocation) {
                        console.log('Using updated location data for hover:', currentLocation.employee_name,
                            currentLocation.last_update);
                        showFloatingPanel(currentLocation);
                        highlightEmployee(currentLocation.employee_id);
                    } else {
                        console.log('Fallback to original location data for hover');
                        showFloatingPanel(location);
                        highlightEmployee(location.employee_id);
                    }
                });

                markerEl.addEventListener('mouseleave', () => {
                    hideFloatingPanel();
                    // Remove highlight after a short delay
                    setTimeout(() => {
                        document.querySelectorAll('.employee-card').forEach(card => {
                            card.classList.remove('active');
                        });
                    }, 300);
                });

                // Add click event for zoom to center
                markerEl.addEventListener('click', (e) => {
                    e.stopPropagation(); // Prevent map click
                    // Get current/updated location data for click event too
                    const currentLocation = currentLocations.find(loc => loc.employee_id === location.employee_id);
                    if (currentLocation) {
                        map.flyTo({
                            center: [currentLocation.longitude, currentLocation.latitude],
                            zoom: 18,
                            duration: 1000
                        });
                        // Show floating panel for a moment after click
                        showFloatingPanel(currentLocation);
                        setTimeout(() => {
                            hideFloatingPanel();
                        }, 3000);
                    }
                });

                // Store event listeners reference to prevent duplicates
                markerEl._eventListenersAdded = true;
            }

            function addMarkerClickEvent(marker, location) {
                // This function ensures existing markers have click events
                const markerEl = marker.getElement();
                if (!markerEl._eventListenersAdded) {
                    addMarkerEventListeners(marker, location);
                }
            }

            function animateMarkerToPosition(marker, newPosition) {
                const markerEl = marker.getElement();
                markerEl.style.transition = 'transform 1s ease-out';

                // Animate marker movement
                const startPos = marker.getLngLat();
                const endPos = newPosition;
                const duration = 1000; // 1 second
                const startTime = Date.now();

                function animate() {
                    const elapsed = Date.now() - startTime;
                    const progress = Math.min(elapsed / duration, 1);

                    // Easing function for smooth animation
                    const eased = 1 - Math.pow(1 - progress, 3);

                    const lat = startPos.lat + (endPos[1] - startPos.lat) * eased;
                    const lng = startPos.lng + (endPos[0] - startPos.lng) * eased;

                    marker.setLngLat([lng, lat]);

                    if (progress < 1) {
                        requestAnimationFrame(animate);
                    }
                }

                requestAnimationFrame(animate);
            }

            function updateMarkerStatus(marker, location) {
                const markerEl = marker.getElement();

                // Update marker class for status
                markerEl.className = `custom-marker ${getMarkerStatusClass(location)}`;

                // Update marker title with current info
                markerEl.title = location.employee_name;
            }

            function getMarkerStatusClass(location) {
                const isOnline = isRecentUpdate(location.last_update);

                // Parse info to check for mocked GPS
                let info = {};
                try {
                    if (location.info) {
                        if (typeof location.info === 'string') {
                            info = JSON.parse(location.info);
                        } else if (typeof location.info === 'object') {
                            info = location.info;
                        }
                    }
                } catch (e) {
                    info = {};
                }

                console.log('Marker status for', location.employee_name, ':', {
                    lastUpdate: location.last_update,
                    isOnline: isOnline,
                    isMocked: info.mocked === true
                });

                // Priority: mocked > offline > online
                if (info.mocked === true) {
                    return 'marker-mocked';
                } else if (isOnline) {
                    return 'marker-online';
                } else {
                    return 'marker-offline';
                }
            }

            function animateCounter(element, targetValue) {
                const currentValue = parseInt(element.textContent) || 0;
                const duration = 500;
                const startTime = Date.now();

                function updateCounter() {
                    const elapsed = Date.now() - startTime;
                    const progress = Math.min(elapsed / duration, 1);
                    const currentCount = Math.round(currentValue + (targetValue - currentValue) * progress);

                    element.textContent = currentCount;

                    if (progress < 1) {
                        requestAnimationFrame(updateCounter);
                    }
                }

                requestAnimationFrame(updateCounter);
            }

            function shouldUpdateBounds() {
                // Only update bounds if this is initial load or significant filter change
                return employeeMarkers.size === 0 || Date.now() - lastUpdateTimestamp > 10000;
            }

            function getDistance(lat1, lng1, lat2, lng2) {
                const R = 6371; // Earth's radius in km
                const dLat = (lat2 - lat1) * Math.PI / 180;
                const dLng = (lng2 - lng1) * Math.PI / 180;
                const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                    Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                    Math.sin(dLng / 2) * Math.sin(dLng / 2);
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                return R * c;
            } // Floating panel functions
            function showFloatingPanel(location) {
                const panel = document.getElementById('floating-info-panel');
                const isOnline = isRecentUpdate(location.last_update);

                console.log('showFloatingPanel data:', {
                    employee_id: location.employee_id,
                    employee_name: location.employee_name,
                    info_raw: location.info,
                    last_update: location.last_update,
                    isOnline: isOnline,
                    timestamp: new Date().toISOString()
                }); // Debug log

                // Parse info JSON if available with better error handling
                let info = {};
                try {
                    if (location.info) {
                        if (typeof location.info === 'string') {
                            info = JSON.parse(location.info);
                        } else if (typeof location.info === 'object') {
                            info = location.info;
                        }
                    }
                    console.log('Parsed info:', info); // Debug log
                } catch (e) {
                    console.log('Info parsing error:', e, 'Raw info:', location.info);
                    info = {};
                } // Update panel content
                document.getElementById('floating-avatar').textContent = location.employee_name.charAt(0).toUpperCase();

                // Set avatar color based on status priority: mocked > offline > online
                if (info.mocked === true) {
                    document.getElementById('floating-avatar').style.backgroundColor = '#f59e0b';
                } else if (isOnline) {
                    document.getElementById('floating-avatar').style.backgroundColor = '#10b981';
                } else {
                    document.getElementById('floating-avatar').style.backgroundColor = '#ef4444';
                }

                document.getElementById('floating-name').textContent = location.employee_name;

                // Improved status display with time info and mocked warning
                let statusText = isOnline ? 'Online' : 'Offline';
                if (info.mocked === true) {
                    statusText = 'Fake GPS Detected';
                }
                const timeSinceUpdate = getTimeSinceUpdate(location.last_update);
                document.getElementById('floating-status').textContent = `${statusText} ${timeSinceUpdate}`;

                // Set status color
                let statusClass = 'floating-info-status ';
                if (info.mocked === true) {
                    statusClass += 'text-amber-600';
                } else if (isOnline) {
                    statusClass += 'text-green-600';
                } else {
                    statusClass += 'text-red-600';
                }
                document.getElementById('floating-status').className = statusClass;

                document.getElementById('floating-department').textContent = location.department || 'N/A';
                document.getElementById('floating-position').textContent = location.position || 'N/A';

                // Format speed with better handling
                document.getElementById('floating-speed').textContent = info.speed !== undefined ?
                    `${Math.round(info.speed * 3.6 * 100) / 100} km/h` : 'N/A';

                // Format heading
                document.getElementById('floating-heading').textContent = info.heading !== undefined ?
                    `${Math.round(info.heading)}°` : 'N/A';

                // Format accuracy - prioritize info.accuracy over location.accuracy
                document.getElementById('floating-accuracy').textContent = info.accuracy !== undefined ?
                    `${Math.round(info.accuracy * 100) / 100}m` :
                    (location.accuracy ? `${location.accuracy}m` : 'N/A'); // Format altitude
                document.getElementById('floating-altitude').textContent = info.altitude !== undefined ?
                    `${Math.round(info.altitude * 100) / 100}m` : 'N/A';

                // Show/hide mocked GPS warning
                const mockedWarning = document.getElementById('floating-mocked-warning');
                if (info.mocked === true) {
                    mockedWarning.style.display = 'block';
                } else {
                    mockedWarning.style.display = 'none';
                }

                // Format last update with better date handling
                document.getElementById('floating-last-update').textContent = formatDateTime(location.last_update);

                // Show panel
                panel.classList.add('show');
            }

            function hideFloatingPanel() {
                const panel = document.getElementById('floating-info-panel');
                panel.classList.remove('show');
            }

            // Form change handlers for real-time filtering
            document.addEventListener('DOMContentLoaded', function() {
                // Listen for form changes
                const form = document.querySelector('form');
                if (form) {
                    form.addEventListener('change', function() {
                        setTimeout(() => {
                            updateLocationsQuietly();
                        }, 100);
                    });
                }
            });

            // Cleanup when leaving page
            window.addEventListener('beforeunload', function() {
                if (updateInterval) {
                    clearInterval(updateInterval);
                }
            }); // Missing utility functions
            function isRecentUpdate(lastUpdateTime) {
                if (!lastUpdateTime) return false;

                try {
                    let updateTime;

                    // Handle different date formats
                    if (typeof lastUpdateTime === 'string') {
                        // Try parsing as ISO string first
                        updateTime = new Date(lastUpdateTime);

                        // If invalid, try parsing as local date string
                        if (isNaN(updateTime.getTime())) {
                            updateTime = new Date(lastUpdateTime.replace(/-/g, '/'));
                        }
                    } else if (typeof lastUpdateTime === 'number') {
                        updateTime = new Date(lastUpdateTime);
                    } else {
                        return false;
                    }

                    // Check if date is valid
                    if (isNaN(updateTime.getTime())) {
                        console.log('Invalid date in isRecentUpdate:', lastUpdateTime);
                        return false;
                    }

                    const now = new Date();
                    const diffMinutes = (now - updateTime) / (1000 * 60);

                    console.log('Time comparison:', {
                        lastUpdate: lastUpdateTime,
                        parsedUpdate: updateTime.toISOString(),
                        now: now.toISOString(),
                        diffMinutes: diffMinutes
                    });

                    // Consider recent if updated within last 10 minutes (more realistic threshold)
                    return diffMinutes <= 10;
                } catch (error) {
                    console.error('Error in isRecentUpdate:', error, 'Input:', lastUpdateTime);
                    return false;
                }
            }

            function createPopupContent(location) {
                const isOnline = isRecentUpdate(location.last_update);

                // Parse info for additional details
                let info = {};
                try {
                    if (location.info) {
                        if (typeof location.info === 'string') {
                            info = JSON.parse(location.info);
                        } else if (typeof location.info === 'object') {
                            info = location.info;
                        }
                    }
                } catch (e) {
                    console.log('Popup info parsing error:', e);
                    info = {};
                }

                // Determine status and colors
                let statusText = isOnline ? 'Online' : 'Offline';
                let statusClass = isOnline ? 'text-green-600' : 'text-red-600';
                let avatarColor = isOnline ? 'bg-green-500' : 'bg-red-500';

                if (info.mocked === true) {
                    statusText = 'Fake GPS Detected';
                    statusClass = 'text-amber-600';
                    avatarColor = 'bg-amber-500';
                }

                const timeSinceUpdate = getTimeSinceUpdate(location.last_update);

                let mockedWarning = '';
                if (info.mocked === true) {
                    mockedWarning = `
                        <div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 rounded text-xs mb-3">
                            <strong>⚠ Warning:</strong> This employee is using fake GPS!
                        </div>
                    `;
                }

                return `
                <div class="employee-popup bg-white dark:bg-slate-950">
                    <div class="flex items-center space-x-3 mb-3">
                        <div class="w-10 h-10 ${avatarColor} rounded-full flex items-center justify-center text-white font-semibold">
                            ${location.employee_name.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-slate-100">${location.employee_name}</h3>
                            <p class="text-sm ${statusClass}">${statusText} ${timeSinceUpdate}</p>
                        </div>
                    </div>
                    
                    ${mockedWarning}
                    
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-slate-400">Department:</span>
                            <span class="font-medium text-gray-900 dark:text-slate-100">${location.department || 'N/A'}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-slate-400">Position:</span>
                            <span class="font-medium text-gray-900 dark:text-slate-100">${location.position || 'N/A'}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-slate-400">Speed:</span>
                            <span class="font-medium text-gray-900 dark:text-slate-100">${info.speed !== undefined ? Math.round(info.speed * 3.6 * 100) / 100 + ' km/h' : 'N/A'}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-slate-400">Accuracy:</span>
                            <span class="font-medium text-gray-900 dark:text-slate-100">${info.accuracy !== undefined ? Math.round(info.accuracy * 100) / 100 + 'm' : (location.accuracy ? location.accuracy + 'm' : 'N/A')}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-slate-400">Altitude:</span>
                            <span class="font-medium text-gray-900 dark:text-slate-100">${info.altitude !== undefined ? Math.round(info.altitude * 100) / 100 + 'm' : 'N/A'}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-slate-400">Last Update:</span>
                            <span class="font-medium text-gray-900 dark:text-slate-100">${formatDateTime(location.last_update)}</span>
                        </div>
                    </div>
                </div>
            `;
            }

            function updateEmployeesList(locations) {
                const listContainer = document.getElementById('employees-list');
                console.log('updateEmployeesList called with', locations.length, 'locations'); // Debug log
                console.log('listContainer found:', !!listContainer); // Debug log

                if (!listContainer) return;

                if (locations.length === 0) {
                    listContainer.innerHTML = `
                    <div class="text-center py-8 text-gray-500 dark:text-slate-400">
                        <p>No active employees found</p>
                    </div>
                `;
                    return;
                }

                // Sort by mocked status first, then online status, then name
                const sortedLocations = locations.sort((a, b) => {
                    // Parse info for both locations
                    let aInfo = {},
                        bInfo = {};
                    try {
                        if (a.info) {
                            aInfo = typeof a.info === 'string' ? JSON.parse(a.info) : a.info;
                        }
                        if (b.info) {
                            bInfo = typeof b.info === 'string' ? JSON.parse(b.info) : b.info;
                        }
                    } catch (e) {
                        // Keep empty objects if parsing fails
                    }

                    const aOnline = isRecentUpdate(a.last_update);
                    const bOnline = isRecentUpdate(b.last_update);
                    const aMocked = aInfo.mocked === true;
                    const bMocked = bInfo.mocked === true;

                    // Priority: mocked first, then online, then alphabetical
                    if (aMocked && !bMocked) return -1;
                    if (!aMocked && bMocked) return 1;
                    if (aOnline && !bOnline) return -1;
                    if (!aOnline && bOnline) return 1;
                    return a.employee_name.localeCompare(b.employee_name);
                });
                listContainer.innerHTML = sortedLocations.map(location => {
                    const isOnline = isRecentUpdate(location.last_update);

                    // Parse info to check for mocked GPS
                    let info = {};
                    try {
                        if (location.info) {
                            if (typeof location.info === 'string') {
                                info = JSON.parse(location.info);
                            } else if (typeof location.info === 'object') {
                                info = location.info;
                            }
                        }
                    } catch (e) {
                        info = {};
                    }

                    // Determine status and colors
                    let statusText = isOnline ? 'Online' : 'Offline';
                    let statusClass = isOnline ? 'text-green-600' : 'text-red-600';
                    let avatarColor = isOnline ? 'bg-green-500' : 'bg-red-500';
                    let badgeClass = isOnline ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                    let dotClass = isOnline ? 'bg-green-400' : 'bg-red-400';

                    if (info.mocked === true) {
                        statusText = 'Fake GPS';
                        statusClass = 'text-amber-600';
                        avatarColor = 'bg-amber-500';
                        badgeClass = 'bg-amber-100 text-amber-800';
                        dotClass = 'bg-amber-400';
                    }

                    const timeSinceUpdate = getTimeSinceUpdate(location.last_update);

                    let mockedIndicator = '';
                    if (info.mocked === true) {
                        mockedIndicator = '<span class="text-red-500 text-xs ml-1">⚠</span>';
                    }

                    return `
                    <div class="employee-card border rounded-lg p-4 hover:shadow-md transition-all cursor-pointer bg-white dark:bg-slate-950 border-slate-200 dark:border-slate-800" 
                         data-employee-id="${location.employee_id}"
                         onclick="focusOnEmployee(${location.employee_id}, ${location.latitude}, ${location.longitude})"
                         onmouseenter="showMarkerPopup(${location.employee_id})"
                         onmouseleave="hideMarkerPopup(${location.employee_id})">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 ${avatarColor} rounded-full flex items-center justify-center text-white font-semibold">
                                    ${location.employee_name.charAt(0).toUpperCase()}
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-slate-100">${location.employee_name}${mockedIndicator}</h4>
                                    <p class="text-sm text-gray-500 dark:text-slate-400">${location.department || 'N/A'}</p>
                                    <p class="text-xs text-gray-400 dark:text-slate-500">${location.position || 'N/A'}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${badgeClass}">
                                    <div class="w-2 h-2 ${dotClass} rounded-full mr-1"></div>
                                    ${statusText}
                                </span>
                                <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">${timeSinceUpdate}</p>                            </div>
                        </div>
                    </div>
                `;
                }).join('');

                console.log('Employee list HTML generated, cards count:', sortedLocations.length); // Debug log
            }

            function updateTimestamp() {
                const now = new Date();
                const timeString = now.toLocaleTimeString('en-US', {
                    hour12: false,
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });

                const updateTimeElement = document.getElementById('update-time');
                if (updateTimeElement) {
                    updateTimeElement.textContent = timeString;
                }
            }

            function forceRefresh() {
                const refreshBtn = document.getElementById('refresh-btn');
                if (refreshBtn) {
                    refreshBtn.classList.add('animate-spin');
                    refreshBtn.disabled = true;
                }

                updateLocationsQuietly();

                setTimeout(() => {
                    if (refreshBtn) {
                        refreshBtn.classList.remove('animate-spin');
                        refreshBtn.disabled = false;
                    }
                }, 1000);
            }

            function highlightEmployee(employeeId) {
                // Remove previous highlights
                document.querySelectorAll('.employee-card').forEach(card => {
                    card.classList.remove('active');
                });

                // Highlight selected employee card
                const employeeCard = document.querySelector(`[data-employee-id="${employeeId}"]`);
                if (employeeCard) {
                    employeeCard.classList.add('active');
                    employeeCard.scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest'
                    });
                }
            }

            function focusOnEmployee(employeeId, latitude, longitude) {
                // Center map on employee
                map.flyTo({
                    center: [longitude, latitude],
                    zoom: 16,
                    duration: 1500
                });

                // Show floating panel for the employee
                const location = currentLocations.find(loc => loc.employee_id === employeeId);
                if (location) {
                    showFloatingPanel(location);
                    // Auto-hide panel after 3 seconds
                    setTimeout(() => {
                        hideFloatingPanel();
                    }, 3000);
                }

                // Highlight employee in list
                highlightEmployee(employeeId);
            }

            function showMarkerPopup(employeeId) {
                // Find location data for this employee
                const location = currentLocations.find(loc => loc.employee_id === employeeId);
                if (location) {
                    showFloatingPanel(location);
                    highlightEmployee(employeeId);
                }
            }

            function hideMarkerPopup(employeeId) {
                hideFloatingPanel();
                // Remove highlight after a short delay
                setTimeout(() => {
                    document.querySelectorAll('.employee-card').forEach(card => {
                        card.classList.remove('active');
                    });
                }, 300);
            }

            function centerMapOnLocation(latitude, longitude) {
                map.flyTo({
                    center: [longitude, latitude],
                    zoom: 18,
                    duration: 1000
                });
            }

            function formatDateTime(dateTimeString) {
                if (!dateTimeString) return 'N/A';

                try {
                    let date;

                    // Handle different date formats
                    if (typeof dateTimeString === 'string') {
                        // Handle ISO string format like "2025-06-18T21:24:20.000Z"
                        date = new Date(dateTimeString);
                    } else if (typeof dateTimeString === 'number') {
                        // Handle timestamp
                        date = new Date(dateTimeString);
                    } else {
                        return 'Invalid Date';
                    }

                    // Check if date is valid
                    if (isNaN(date.getTime())) {
                        console.log('Invalid date:', dateTimeString);
                        return 'Invalid Date';
                    }

                    // Format the date
                    return date.toLocaleString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: true
                    });
                } catch (error) {
                    console.log('Date formatting error:', error, 'Input:', dateTimeString);
                    return 'Invalid Date';
                }
            }

            function getTimeSinceUpdate(lastUpdateTime) {
                if (!lastUpdateTime) return '';

                try {
                    let updateTime;

                    if (typeof lastUpdateTime === 'string') {
                        updateTime = new Date(lastUpdateTime);
                        if (isNaN(updateTime.getTime())) {
                            updateTime = new Date(lastUpdateTime.replace(/-/g, '/'));
                        }
                    } else if (typeof lastUpdateTime === 'number') {
                        updateTime = new Date(lastUpdateTime);
                    } else {
                        return '';
                    }

                    if (isNaN(updateTime.getTime())) {
                        return '';
                    }

                    const now = new Date();
                    const diffMinutes = Math.floor((now - updateTime) / (1000 * 60));

                    if (diffMinutes < 1) {
                        return '(just now)';
                    } else if (diffMinutes < 60) {
                        return `(${diffMinutes}m ago)`;
                    } else {
                        const diffHours = Math.floor(diffMinutes / 60);
                        return `(${diffHours}h ago)`;
                    }
                } catch (error) {
                    return '';
                }
            }
        </script>
    @endpush
</x-filament-panels::page>
