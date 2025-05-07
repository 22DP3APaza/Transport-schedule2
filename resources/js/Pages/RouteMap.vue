<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const { t } = useI18n();

const props = defineProps({
    route: Object,
    trip: Object,
    shapePoints: Array,
    stops: Array,
    allStops: Array // New prop for all GTFS stops
});

const mapContainer = ref(null);
const map = ref(null);

// Optional: Filter out Mozilla deprecation warnings
const originalWarn = console.warn;
console.warn = function(...args) {
    if (!args[0].includes('mozPressure') && !args[0].includes('mozInputSource')) {
        originalWarn.apply(console, args);
    }
};

const fixLeafletIcons = () => {
    delete L.Icon.Default.prototype._getIconUrl;

    L.Icon.Default.mergeOptions({
        iconRetinaUrl: '/images/markers/marker-icon.svg',
        iconUrl: '/images/markers/marker-icon.svg',
        shadowUrl: null,
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        tooltipAnchor: [16, -28],
        shadowSize: [41, 41]
    });
};

onMounted(() => {
    // Suppress Mozilla-specific event warnings
    if (typeof window !== 'undefined') {
        window.addEventListener('mozpressure', e => e.preventDefault(), { passive: false });
        window.addEventListener('mozinputsources', e => e.preventDefault(), { passive: false });
    }

    fixLeafletIcons();

    map.value = L.map(mapContainer.value, {
        zoomControl: false
    }).setView([56.946, 24.105], 13);

    // Move zoom control to right
    L.control.zoom({ position: 'topright' }).addTo(map.value);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map.value);

    // Draw route polyline
    if (props.shapePoints.length > 0) {
        const routeCoordinates = props.shapePoints.map(point => [
            parseFloat(point.shape_pt_lat),
            parseFloat(point.shape_pt_lon)
        ]);
        L.polyline(routeCoordinates, {
            color: `#${props.route.route_color || '0078ff'}`
        }).addTo(map.value);
    }

    // Icon for route stops
    const stopIcon = L.icon({
        iconUrl: '/images/markers/marker-icon.svg',
        iconSize: [10, 10],
    });

    // Add route stops
    props.stops.forEach(stop => {
        L.marker([stop.stop_lat, stop.stop_lon], { icon: stopIcon })
            .addTo(map.value)
            .bindPopup(`<strong>${stop.stop_name}</strong><br>Seq: ${stop.stop_sequence}`);
    });

    // Special icon for last stop
    if (props.stops.length > 0) {
        const lastStop = props.stops.reduce((prev, curr) =>
            curr.stop_sequence > prev.stop_sequence ? curr : prev
        );

        const lastStopIcon = L.icon({
            iconUrl: '/images/markers/marker-icon2.svg',
            iconSize: [20, 20],
        });

        L.marker([lastStop.stop_lat, lastStop.stop_lon], { icon: lastStopIcon })
            .addTo(map.value)
            .bindPopup(`<strong>${lastStop.stop_name}</strong><br>(Last Stop)`);
    }

    // Icon for all GTFS stops
    const allStopsIcon = L.icon({
        iconUrl: '/images/markers/marker-icon3.svg',
        iconSize: [8, 8],
    });

    // Set of stops that are part of the route (we don't want to duplicate them with marker-icon3.svg)
    const routeStopCoordinates = new Set(
        props.stops.map(stop => `${stop.stop_lat},${stop.stop_lon}`)
    );

    // Add all GTFS stops, skipping those already part of the route
    props.allStops.forEach(stop => {
        const stopCoordinates = `${stop.stop_lat},${stop.stop_lon}`;

        if (!routeStopCoordinates.has(stopCoordinates)) {
            L.marker([stop.stop_lat, stop.stop_lon], { icon: allStopsIcon })
                .addTo(map.value)
                .bindPopup(`<strong>${stop.stop_name}</strong>`);

        }
    });

    // Fit map to all coordinates
    const allCoordinates = [
        ...props.shapePoints.map(p => [parseFloat(p.shape_pt_lat), parseFloat(p.shape_pt_lon)]),
        ...props.stops.map(s => [s.stop_lat, s.stop_lon]),
        ...props.allStops.filter(s => !routeStopCoordinates.has(`${s.stop_lat},${s.stop_lon}`)).map(s => [s.stop_lat, s.stop_lon])
    ];

    if (allCoordinates.length > 0) {
        map.value.fitBounds(allCoordinates, { padding: [50, 50] });
    }
});
</script>

<template>
    <Head :title="`${t('viewOnMap')} - ${route.route_short_name}`" />

    <div class="fixed inset-0 z-0">
        <!-- Back button styled like Leaflet controls -->
        <div class="leaflet-top leaflet-left">
            <div class="leaflet-control leaflet-bar">
                <a
                    href="#"
                    @click.prevent="$inertia.visit(`/route/details/${route.route_id}/${trip.trip_id}`)"
                    class="block text-center w-16 h-16 leading-[4rem] text-2xl"
                    title="Back"
                >
                    ←
                </a>
            </div>
        </div>

        <!-- Full-page map -->
        <div ref="mapContainer" class="w-full h-full"></div>
    </div>
</template>
