<script setup>
import { Head, Link, router } from '@inertiajs/vue3'; // Import 'router' here
import { ref, computed, onMounted, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css'; // Ensure Leaflet CSS is imported

const props = defineProps({
    trip: Object,
    route: Object,
    stops: Array,
    allStops: Array,
    selectedStopId: String,
    selectedDepartureTime: String
});

const { t } = useI18n();

// Refs for Leaflet map
const mapContainer = ref(null);
const map = ref(null);
const routePolyline = ref(null);
const stopMarkers = ref({}); // Stores markers for stops on the current trip
const lastStopMarker = ref(null); // Stores the marker for the last stop of the trip
const allStopsMarkers = ref([]); // Stores markers for all GTFS stops not on the current trip
const showAllStops = ref(false);

// Computed property to format stops and determine highlight status
const formattedStops = computed(() => {
    return props.stops.map(stop => ({
        ...stop,
        isHighlighted: stop.stop_id === props.selectedStopId &&
                       stop.departure_time === props.selectedDepartureTime
    }));
});

// Formats time strings (e.g., '25:00' to '01:00')
const formatTime = (time) => {
    if (!time) return '';
    const [hours, minutes] = time.split(':');
    if (!hours || !minutes) return time;
    const numHours = parseInt(hours);
    if (numHours >= 24) {
        const adjustedHours = numHours - 24;
        return `${adjustedHours.toString().padStart(2, '0')}:${minutes}`;
    }
    return `${hours}:${minutes}`;
};

// Suppress specific Mozilla deprecation warnings in the console
const originalWarn = console.warn;
console.warn = function(...args) {
    if (!args[0].includes('mozPressure') && !args[0].includes('mozInputSource')) {
        originalWarn.apply(console, args);
    }
};

// Fix Leaflet's default icon paths, necessary for many bundlers like Webpack/Vite
// This sets the *default* icon options. Custom L.icon instances can override these.
const fixLeafletIcons = () => {
    delete L.Icon.Default.prototype._getIconUrl;

    L.Icon.Default.mergeOptions({
        iconRetinaUrl: '/images/markers/marker-icon2.svg',
        iconUrl: '/images/markers/marker-icon2.svg',
        shadowUrl: null, // No shadow for simpler icons
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        tooltipAnchor: [16, -28],
        shadowSize: [41, 41]
    });
    console.log("Leaflet default icons fixed.");
};

// Toggles visibility of all additional GTFS stops on the map
const toggleAllStops = () => {
    showAllStops.value = !showAllStops.value;
    console.log(`Toggling all stops. showAllStops is now: ${showAllStops.value}`);
    allStopsMarkers.value.forEach(marker => {
        if (showAllStops.value) {
            marker.addTo(map.value);
            console.log(`Added all stop marker to map.`);
        } else {
            marker.remove();
            console.log(`Removed all stop marker from map.`);
        }
    });
    updateMapBounds(); // Update map bounds after toggling
};

// Draws/redraws all map elements: route polyline, trip stops, and optionally all other GTFS stops
const drawMapElements = () => {
    console.log("--- drawMapElements called ---");
    console.log("Current props.stops:", props.stops);
    console.log("Current props.allStops:", props.allStops);

    if (!map.value) {
        console.warn("Map not initialized when trying to draw elements. Aborting drawMapElements.");
        return;
    }

    // Clear existing layers before redrawing to prevent duplicates
    if (routePolyline.value) {
        map.value.removeLayer(routePolyline.value);
        routePolyline.value = null;
        console.log("Cleared existing route polyline.");
    }
    Object.values(stopMarkers.value).forEach(marker => {
        map.value.removeLayer(marker);
    });
    stopMarkers.value = {}; // Reset stopMarkers object
    console.log("Cleared existing trip stop markers.");

    if (lastStopMarker.value) {
        map.value.removeLayer(lastStopMarker.value);
        lastStopMarker.value = null;
        console.log("Cleared existing last stop marker.");
    }
    allStopsMarkers.value.forEach(marker => marker.remove());
    allStopsMarkers.value = []; // Reset allStopsMarkers array
    console.log("Cleared existing all GTFS stop markers.");

    // The section for drawing the route polyline has been removed as requested.
    // This means the lines connecting the stops will no longer be displayed.

    // Define icon for regular route stops
    const stopIcon = L.icon({
        iconUrl: '/images/markers/marker-icon2.svg',
        iconSize: [25, 41], // Increased size for better visibility
        iconAnchor: [12, 41] // Adjusted anchor for new size
    });
    console.log(`Regular stop icon URL: ${stopIcon.options.iconUrl}, Size: ${stopIcon.options.iconSize}`);

    // Add markers for each stop on the current trip
    if (props.stops && props.stops.length > 0) {
        console.log("Adding markers for trip stops...");
        props.stops.forEach(stop => {
            const lat = parseFloat(stop.stop_lat);
            const lon = parseFloat(stop.stop_lon);
            if (isNaN(lat) || isNaN(lon)) {
                console.error(`Invalid coordinates for trip stop ${stop.stop_id}: (${stop.stop_lat}, ${stop.stop_lon}). Skipping marker creation.`);
                return; // Skip this marker if coordinates are invalid
            }
            console.log(`Creating marker for trip stop '${stop.stop_name}' at [${lat}, ${lon}]`);
            const marker = L.marker([lat, lon], { icon: stopIcon })
                .addTo(map.value)
                .bindPopup(`<strong>${stop.stop_name}</strong><br>`);
            stopMarkers.value[stop.stop_id] = marker;
            console.log(`Marker for trip stop '${stop.stop_name}' added and stored.`);
        });
    } else {
        console.warn("No stops data provided to add trip stop markers.");
    }

    // Special icon for the last stop of the trip
    if (props.stops && props.stops.length > 0) {
        const lastStop = props.stops.reduce((prev, curr) =>
            curr.stop_sequence > prev.stop_sequence ? curr : prev
        );
        const lat = parseFloat(lastStop.stop_lat);
        const lon = parseFloat(lastStop.stop_lon);
        if (isNaN(lat) || isNaN(lon)) {
            console.error(`Invalid coordinates for last stop ${lastStop.stop_id}: (${lastStop.stop_lat}, ${lastStop.stop_lon}). Skipping last stop marker.`);
        } else {
            const lastStopIcon = L.icon({
                iconUrl: '/images/markers/marker-icon2.svg', // Distinct icon for the terminus
                iconSize: [20, 20],
                iconAnchor: [10, 10]
            });
            console.log(`Last stop icon URL: ${lastStopIcon.options.iconUrl}, Size: ${lastStopIcon.options.iconSize}`);

            // Remove the smaller marker if it exists for the last stop, then add the larger one
            if (stopMarkers.value[lastStop.stop_id]) {
                map.value.removeLayer(stopMarkers.value[lastStop.stop_id]);
                delete stopMarkers.value[lastStop.stop_id];
                console.log(`Removed regular marker for last stop '${lastStop.stop_name}' to replace with special icon.`);
            }
            console.log(`Creating last stop marker for '${lastStop.stop_name}' at [${lat}, ${lon}]`);
            lastStopMarker.value = L.marker([lat, lon], { icon: lastStopIcon })
                .addTo(map.value)
                .bindPopup(`<strong>${lastStop.stop_name}</strong><br>(${t('lastStop')})`);
            console.log(`Last stop marker for '${lastStop.stop_name}' added.`);
        }
    }

    // Define icon for all other GTFS stops (not on the current trip route)
    const allStopsIcon = L.icon({
        iconUrl: '/images/markers/marker-icon3.svg',
        iconSize: [15, 15], // Increased size for better visibility
        iconAnchor: [7, 7] // Adjusted anchor for new size
    });
    console.log(`All GTFS stops icon URL: ${allStopsIcon.options.iconUrl}, Size: ${allStopsIcon.options.iconSize}`);

    // Create a set of coordinates for stops already on the current route to avoid duplicates
    const routeStopCoordinates = new Set(
        (props.stops || []).map(stop => `${stop.stop_lat},${stop.stop_lon}`)
    );

    if (props.allStops && props.allStops.length > 0) {
        console.log("Adding markers for all other GTFS stops...");
        props.allStops.forEach(stop => {
            const stopCoordinates = `${stop.stop_lat},${stop.stop_lon}`;
            if (!routeStopCoordinates.has(stopCoordinates)) { // Only add if not already on the trip route
                const lat = parseFloat(stop.stop_lat);
                const lon = parseFloat(stop.stop_lon);
                if (isNaN(lat) || isNaN(lon)) {
                    console.error(`Invalid coordinates for allStop ${stop.stop_id}: (${stop.stop_lat}, ${stop.stop_lon}). Skipping marker creation.`);
                    return;
                }
                console.log(`Creating allStop marker for '${stop.stop_name}' at [${lat}, ${lon}]`);
                const marker = L.marker([lat, lon], { icon: allStopsIcon })
                    .bindPopup(`<strong>${stop.stop_name}</strong>`);
                if (showAllStops.value) { // Only add to map if toggle is active
                    marker.addTo(map.value);
                    console.log(`All stop marker for '${stop.stop_name}' added to map (showAllStops is true).`);
                }
                allStopsMarkers.value.push(marker);
                console.log(`All stop marker for '${stop.stop_name}' stored.`);
            }
        });
    } else {
        console.log("No allStops data provided or allStops is empty. No additional GTFS stops to add.");
    }
    updateMapBounds(); // Fit map to new elements
    console.log("--- drawMapElements finished ---");
};

// Updates the map view to fit all relevant markers and polyline
const updateMapBounds = () => {
    console.log("updateMapBounds called.");
    if (!map.value) {
        console.warn("Map not initialized when trying to update bounds.");
        return;
    }

    const allCoordinatesForBounds = [
        ...(props.stops || []).map(s => [parseFloat(s.stop_lat), parseFloat(s.stop_lon)]),
        ...(showAllStops.value ? (props.allStops || []).filter(s => {
            // Filter out stops already on the route to avoid duplicate coordinates in bounds calculation
            const routeStopCoordinates = new Set((props.stops || []).map(stop => `${stop.stop_lat},${stop.stop_lon}`));
            return !routeStopCoordinates.has(`${s.stop_lat},${s.stop_lon}`);
        }).map(s => [parseFloat(s.stop_lat), parseFloat(s.stop_lon)]) : [])
    ].filter(coord => !isNaN(coord[0]) && !isNaN(coord[1])); // Filter out any NaN coordinates

    if (allCoordinatesForBounds.length > 0) {
        console.log("Coordinates for map bounds:", allCoordinatesForBounds);
        map.value.fitBounds(allCoordinatesForBounds, { padding: [50, 50] });
        console.log("Map bounds fitted.");
    } else {
        console.warn("No valid coordinates to fit bounds. Centering map on default location.");
        map.value.setView([56.946, 24.105], 13); // Default view for Riga, Latvia
    }
};

// Watch for changes in selected stop to highlight it on the map
watch([() => props.selectedStopId, () => props.selectedDepartureTime], ([newStopId, newDepartureTime]) => {
    console.log(`Selected stop changed: ID=${newStopId}, Time=${newDepartureTime}`);
    if (!map.value) return;

    // Define highlight icon
    const highlightedIcon = L.icon({
        iconUrl: '/images/markers/marker-icon-highlight.svg', // Ensure this icon exists!
        iconSize: [24, 24],
        iconAnchor: [12, 12]
    });
    console.log(`Highlight icon URL: ${highlightedIcon.options.iconUrl}`);

    // Define regular stop icon (for resetting)
    const regularStopIcon = L.icon({
        iconUrl: '/images/markers/marker-icon.svg',
        iconSize: [25, 41], // Consistent with updated stopIcon
        iconAnchor: [12, 41] // Consistent with updated stopIcon
    });

    // Define last stop icon (for resetting)
    const lastStopDefaultIcon = L.icon({
        iconUrl: '/images/markers/marker-icon2.svg',
        iconSize: [20, 20],
        iconAnchor: [10, 10]
    });

    // Reset previous highlight for all trip stop markers
    Object.values(stopMarkers.value).forEach(marker => {
        marker.setIcon(regularStopIcon);
    });
    console.log("Reset all trip stop markers to regular icon.");

    // Reset last stop marker if it's not the currently highlighted one
    if (lastStopMarker.value && props.stops.length > 0) {
        const lastStop = props.stops.reduce((prev, curr) => curr.stop_sequence > prev.stop_sequence ? curr : prev);
        // Check if the last stop is the one currently being highlighted
        const isLastStopHighlighted = lastStop.stop_id === newStopId && lastStop.departure_time === newDepartureTime;

        if (!isLastStopHighlighted) {
            lastStopMarker.value.setIcon(lastStopDefaultIcon);
            console.log("Reset last stop marker to default icon.");
        }
    }

    // Apply new highlight if a stop is selected
    if (newStopId && newDepartureTime) {
        const highlightedStop = props.stops.find(s => s.stop_id === newStopId && s.departure_time === newDepartureTime);
        if (highlightedStop) {
            console.log(`Applying highlight to stop: ${highlightedStop.stop_name}`);
            // Check if the highlighted stop is a regular trip stop or the last stop
            if (stopMarkers.value[newStopId]) {
                stopMarkers.value[newStopId].setIcon(highlightedIcon);
                console.log(`Highlighted regular stop marker for ${newStopId}.`);
            } else if (lastStopMarker.value &&
                               lastStopMarker.value.getLatLng().lat === parseFloat(highlightedStop.stop_lat) &&
                               lastStopMarker.value.getLatLng().lng === parseFloat(highlightedStop.stop_lon)) {
                lastStopMarker.value.setIcon(highlightedIcon);
                console.log(`Highlighted last stop marker for ${newStopId}.`);
            }

            // Pan map to the highlighted stop
            map.value.panTo([parseFloat(highlightedStop.stop_lat), parseFloat(highlightedStop.stop_lon)]);
            console.log(`Map panned to highlighted stop [${highlightedStop.stop_lat}, ${highlightedStop.stop_lon}].`);
        } else {
            console.warn(`Highlighted stop not found in props.stops for ID=${newStopId}, Time=${newDepartureTime}.`);
        }
    }
}, { immediate: true }); // Run immediately on component mount

// Initialize map on component mount
onMounted(() => {
    console.log("Component mounted. Initializing map...");
    fixLeafletIcons(); // Ensure icons are fixed before map initialization

    // Suppress Mozilla-specific event warnings
    if (typeof window !== 'undefined') {
        window.addEventListener('mozpressure', e => e.preventDefault(), { passive: false });
        window.addEventListener('mozinputsinputsources', e => e.preventDefault(), { passive: false });
    }

    try {
        if (mapContainer.value) {
            const rect = mapContainer.value.getBoundingClientRect();
            console.log(`mapContainer dimensions: Width=${rect.width}px, Height=${rect.height}px`);
            if (rect.width === 0 || rect.height === 0) {
                console.error("Map container has zero dimensions! Map will not display correctly. Check parent CSS.");
            }
        } else {
            console.error("mapContainer ref is null. The div might not be mounted yet or ref is incorrect. Map cannot be initialized.");
            return;
        }

        // Initialize Leaflet map
        map.value = L.map(mapContainer.value, {
            zoomControl: false // Disable default zoom control
        }).setView([56.946, 24.105], 13); // Default view for Riga, Latvia

        // Add zoom control to topright
        L.control.zoom({ position: 'topright' }).addTo(map.value);

        // Add OpenStreetMap tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map.value);
        console.log("Leaflet map initialized and tile layer added.");

        // Draw initial map elements (polyline, markers)
        drawMapElements();

    } catch (error) {
        console.error("Critical Error initializing Leaflet map in triptimes.vue:", error);
    }
});

// Watch for changes in trip data (stops, allStops, route) to redraw map elements
watch([() => props.stops, () => props.allStops, () => props.route], () => {
    console.log("Props (stops, allStops, or route) changed. Redrawing map elements.");
    drawMapElements();
}, { deep: true }); // Deep watch is important for nested object changes

const goBack = () => window.history.back();
</script>

<template>
    <Head :title="t('tripSchedule')" />

    <div class="flex h-screen w-screen bg-gray-100">
        <div class="w-1/3 flex-shrink-0 bg-white p-4 overflow-y-auto shadow-xl rounded-lg m-4">
            <div class="mb-4">
                <Link :href="'/train/details/' + trip.route_id + '/' + trip.trip_id" class="flex items-center text-blue-600 hover:text-blue-800">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-5 h-5 mr-1">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span @click="goBack" class="font-semibold">{{ t('back') }}</span>
                </Link>
            </div>

            <h2 class="text-lg font-bold mb-4 border-b pb-2 text-gray-800">
                {{ trip.route_short_name || t('trainTrip') }} - {{ t('schedule') }}
            </h2>

            <div class="flex-grow overflow-y-auto pr-2">
                <div
                    v-for="stop in formattedStops"
                    :key="stop.stop_id + '-' + stop.departure_time"
                    class="flex items-start mb-3 last:mb-0 cursor-pointer rounded-md p-2 transition-colors duration-200"
                    :class="{ 'bg-blue-100 text-blue-700 font-bold shadow-sm': stop.isHighlighted, 'hover:bg-gray-50': !stop.isHighlighted }"
                    @click="map?.panTo([parseFloat(stop.stop_lat), parseFloat(stop.stop_lon)]); stopMarkers[stop.stop_id]?.openPopup();"
                >
                    <div class="flex-shrink-0 w-16 text-sm text-gray-700 pr-2 pt-1 text-right">
                        {{ formatTime(stop.departure_time) }}
                    </div>
                    <div class="flex-grow border-l-2 pl-4"
                         :class="{ 'border-blue-500': stop.isHighlighted, 'border-gray-300': !stop.isHighlighted }">
                        <div class="text-base text-gray-900">{{ stop.stop_name }}</div>
                        <div v-if="stop.zone_id" class="text-xs text-gray-500">
                            {{ t('zone') }}: {{ stop.fare_zone_name || stop.zone_id }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-grow relative m-4 rounded-lg shadow-xl overflow-hidden">
            <div class="absolute top-4 left-4 z-10">
                <div class="leaflet-control leaflet-bar rounded-lg overflow-hidden">
                    <a
                        href="#"
                        @click.prevent="toggleAllStops"
                        class="w-12 h-12 bg-white flex items-center justify-center rounded-lg shadow-md hover:bg-gray-50 transition-colors duration-200"
                        :title="showAllStops ? t('hideAdditionalStops') : t('showAdditionalStops')"
                    >
                        <img
                            src="/images/markers/marker-icon4.svg"
                            :alt="t('toggleStops')"
                            class="w-6 h-6"
                            :class="{ 'opacity-50': !showAllStops }"
                        />
                    </a>
                </div>
            </div>

            <div ref="mapContainer" class="w-full h-full bg-blue-100"></div>
        </div>
    </div>
</template>
