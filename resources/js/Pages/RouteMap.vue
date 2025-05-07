<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import L from 'leaflet'; // Import Leaflet
import 'leaflet/dist/leaflet.css'; // Import Leaflet CSS

// i18n for translations
const { t } = useI18n();

// Props passed from Laravel backend via Inertia
const props = defineProps({
    route: Object,
    trip: Object,
    shapePoints: Array,
    stops: Array
});

// Ref to map container DOM element
const mapContainer = ref(null);
const map = ref(null);

// Fix for default marker icons in Vite + Leaflet
const fixLeafletIcons = () => {
    delete L.Icon.Default.prototype._getIconUrl;

    L.Icon.Default.mergeOptions({
        iconUrl: '/images/markers/marker-icon.svg',
        iconRetinaUrl: '/images/markers/marker-icon-2x.svg',
        shadowUrl: '/images/markers/marker-shadow.svg'
    });
};

onMounted(() => {
    fixLeafletIcons();

    map.value = L.map(mapContainer.value).setView([56.946, 24.105], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map.value);

    // Draw shape line
    if (props.shapePoints.length > 0) {
        const routeCoordinates = props.shapePoints.map(point => [
            parseFloat(point.shape_pt_lat),
            parseFloat(point.shape_pt_lon)
        ]);

        const routeLine = L.polyline(routeCoordinates, {
            color: `#${props.route.route_color || '0078ff'}`
        }).addTo(map.value);
    }

    // Add stop markers
    props.stops.forEach(stop => {
        L.marker([stop.stop_lat, stop.stop_lon])
            .addTo(map.value)
            .bindPopup(`<strong>${stop.stop_name}</strong><br>Seq: ${stop.stop_sequence}`);
    });

    // Auto-fit bounds
    const allCoordinates = [
        ...props.shapePoints.map(p => [p.shape_pt_lat, p.shape_pt_lon]),
        ...props.stops.map(s => [s.stop_lat, s.stop_lon])
    ];

    if (allCoordinates.length > 0) {
        map.value.fitBounds(allCoordinates, { padding: [50, 50] });
    }
});
</script>

<template>
    <Head :title="`${t('viewOnMap')} - ${route.route_short_name}`" />

    <div class="container mx-auto p-4">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-2xl font-bold">
                {{ route.route_short_name }}: {{ trip.trip_headsign }}
            </h1>
            <button @click="$inertia.visit(`/route/details/${route.route_id}/${trip.trip_id}`)"
                    class="btn btn-outline">
                ← {{ t('back') }}
            </button>
        </div>

        <div class="bg-white rounded-lg shadow-md p-4 dark:bg-gray-800">
            <div ref="mapContainer" class="w-full h-[70vh] rounded-md"></div>
        </div>
    </div>
</template>

