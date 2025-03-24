<script setup>
import { ref, watch } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

// Get the page props
const page = usePage();
const routedata = ref(page.props.route || {});
const trips = ref(page.props.trips || []);
const selectedTrip = ref(page.props.selectedTrip || trips.value[0] || null);
const stops = ref(page.props.stops || []);
const stopTimes = ref([]); // Stores stop times for the selected stop
const selectedStop = ref(null); // Stores the currently selected stop
const showWorkdays = ref(true); // Default to showing workdays

// Function to go back
const goBack = () => window.history.back();

// Watch for changes in selectedTrip and update stops accordingly
watch(selectedTrip, (newTrip) => {
    if (newTrip) {
        fetch(`/route/details/${routedata.value.route_id}/${newTrip.trip_id}/stops`)
            .then(response => response.json())
            .then(data => {
                stops.value = data;
                // Automatically select the first stop if available
                if (data.length > 0) {
                    selectedStop.value = data[0];
                } else {
                    selectedStop.value = null; // Clear selection if no stops are available
                }
            })
            .catch(error => console.error('Error fetching stops:', error));
    }
}, { immediate: true });

const formatTime = (time) => {
    if (!time) return '';
    return time.split(':').slice(0, 2).join(':');
};

// Fetch stop times for the selected stop
const fetchStopTimes = async () => {
    if (!selectedStop.value || !routedata.value || !selectedTrip.value) return;

    const type = showWorkdays.value ? 'workdays' : 'weekends';
    const url = `/stop/times/${selectedStop.value.stop_id}?type=${type}&route_id=${routedata.value.route_id}&trip_id=${selectedTrip.value.trip_id}`;

    try {
        const response = await fetch(url);
        if (!response.ok) throw new Error('Network response was not ok');
        const data = await response.json();

        // Update stop times with formatted departure times
        stopTimes.value = data.map(time => ({
            departure_time: formatTime(time.departure_time)
        }));
    } catch (error) {
        console.error('Error fetching stop times:', error);
    }
};

// Watch for changes in the route, trip, or selected stop and update stop times
watch([routedata, selectedTrip, selectedStop], () => {
    if (selectedStop.value && selectedTrip.value) {
        fetchStopTimes();
    }
});

// Handle time button click
const handleTimeClick = (time) => {
    if (!selectedTrip.value || !selectedTrip.value.trip_id || !selectedStop.value || !time.departure_time) {
        console.error('Missing required data for time click');
        return;
    }

    router.visit(`/stoptimes?trip_id=${selectedTrip.value.trip_id}&stop_id=${selectedStop.value.stop_id}&departure_time=${time.departure_time}`);
};

</script>

<template>
    <Head :title="routedata.route_short_name" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="/images/logo.webp">

    <!-- Header Section -->
    <header class="navbar bg-base-100">
        <div class="navbar-start">
            <a href="/">
                <button class="btn btn-square btn-ghost">
                    <img src="/images/logo.png" alt="Logo" class="h-10">
                </button>
            </a>
        </div>
        <div class="navbar-center">
            <div class="navbar bg-base-100"><a href="/bus" class="btn btn-ghost text-xl">Bus</a></div>
            <div class="navbar bg-base-100"><a href="/trolleybus" class="btn btn-ghost text-xl">Trolleybus</a></div>
            <div class="navbar bg-base-100"><a href="/tram" class="btn btn-ghost text-xl">Tram</a></div>
            <div class="navbar bg-base-100"><a href="/train" class="btn btn-ghost text-xl">Train</a></div>
        </div>
        <div class="navbar-end">
            <div class="dropdown dropdown-end">
                <button tabindex="0" role="button" class="btn btn-square btn-ghost">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block h-5 w-5 stroke-current">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 12a1 1 0 11-2 0 1 1 0 012 0z">
                        </path>
                    </svg>
                </button>
                <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-[1] w-52 p-2 shadow">
                    <li v-if="!$page.props.auth.user">
                        <a :href="route('login')">Login</a>
                    </li>
                    <li><a>Settings</a></li>
                    <li v-if="$page.props.auth.user">
                        <Link :href="route('logout')" method="post">Log Out</Link>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Route Details Section -->
    <div class="container mx-auto mt-6 p-4">
        <button @click="goBack" class="btn btn-outline mb-4">← Back</button>
        <div class="btn btn-square w-10 h-10 flex items-center justify-center text-white hover:brightness-90 transition rounded-md shadow text-sm font-bold" :style="{ backgroundColor: '#' + routedata.route_color }">
            {{ routedata.route_short_name }}
        </div>
        <div class="mt-4">
            <label for="trip-select" class="block text-sm font-medium text-gray-700">Select Trip</label>
            <select id="trip-select" v-model="selectedTrip" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                <option v-for="trip in trips" :key="trip.trip_id" :value="trip">
                    {{ trip.trip_headsign }} (Shape ID: {{ trip.shape_id }})
                </option>
            </select>
        </div>
        <div class="mt-4">
            <label for="stop-select" class="block text-sm font-medium text-gray-700">Select Stop</label>
            <select id="stop-select" v-model="selectedStop" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                <option v-for="stop in stops" :key="stop.stop_id" :value="stop">
                    {{ stop.stop_name }}
                </option>
            </select>
        </div>

        <!-- Stop Times Display -->
        <div v-if="selectedStop" class="mt-6">
            <h2 class="text-lg font-semibold">Stop Times for {{ selectedStop.stop_name }}</h2>
            <div class="flex items-center mb-4">
                <label class="mr-2">Show:</label>
                <button @click="showWorkdays = true; fetchStopTimes()" :class="{'btn-primary': showWorkdays, 'btn-outline': !showWorkdays}" class="btn btn-sm mr-2">
                    Workdays
                </button>
                <button @click="showWorkdays = false; fetchStopTimes()" :class="{'btn-primary': !showWorkdays, 'btn-outline': showWorkdays}" class="btn btn-sm">
                    Weekends
                </button>
            </div>
            <div v-if="stopTimes.length > 0" class="grid grid-cols-[repeat(auto-fill,minmax(60px,1fr))] gap-1 justify-items-center">
                <button
    v-for="(time, index) in stopTimes"
    :key="index"
    class="btn btn-xs border-none bg-transparent hover:bg-primary hover:text-white transition px-2 py-1 relative"
    @click="handleTimeClick(time)"
    :title="selectedTrip?.trip_id"
>
    {{ time.departure_time }}
</button>
            </div>
            <div v-else>
                <p>No stop times available for this stop and trip.</p>
            </div>
        </div>
    </div>
</template>
