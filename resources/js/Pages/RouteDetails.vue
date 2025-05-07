<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();

// Get the page props
const page = usePage();
const routedata = ref(page.props.route || {});
const trips = ref(page.props.trips || []);
const selectedTrip = ref(page.props.selectedTrip || trips.value[0] || null);
const stops = ref(page.props.stops || []);
const stopTimes = ref([]);
const hasWorkdaysData = ref(false);
const hasWeekendsData = ref(false);
const stopTimesData = ref({
    workdays: [],
    weekends: []
});
const selectedStop = ref(null);
const showWorkdays = ref(true);
const currentTime = ref(new Date());

// Theme Management
const currentTheme = ref('light');
const toggleTheme = () => {
    currentTheme.value = currentTheme.value === 'light' ? 'dark' : 'light';
    document.querySelector('html').setAttribute('data-theme', currentTheme.value);
    localStorage.setItem('theme', currentTheme.value);
};

// Language Management
const changeLanguage = (language) => {
    locale.value = language;
    localStorage.setItem('language', language);
};

// Function to go back
const goBack = () => window.history.back();

// Watch for changes in selectedTrip and update stops accordingly
watch(selectedTrip, (newTrip) => {
    if (newTrip) {
        fetch(`/route/details/${routedata.value.route_id}/${newTrip.trip_id}/stops`)
            .then(response => response.json())
            .then(data => {
                stops.value = data;
                if (data.length > 0) {
                    selectedStop.value = data[0];
                } else {
                    selectedStop.value = null;
                }
            })
            .catch(error => console.error(t('errorFetchingStops'), error));
    }
}, { immediate: true });

// Format time to HH:MM
const formatTime = (time) => {
    if (!time) return '';
    return time.split(':').slice(0, 2).join(':');
};

// Check if a time is in the future compared to current time
const isFutureTime = (timeStr) => {
    if (!timeStr) return false;
    const now = currentTime.value;
    const currentHours = now.getHours();
    const currentMinutes = now.getMinutes();
    const [hours, minutes] = timeStr.split(':').map(Number);
    if (hours > currentHours) return true;
    if (hours === currentHours && minutes > currentMinutes) return true;
    return false;
};

// Fetch stop times for the selected stop
const fetchStopTimes = async () => {
    if (!selectedStop.value || !routedata.value || !selectedTrip.value) return;

    // Reset data flags and storage
    hasWorkdaysData.value = false;
    hasWeekendsData.value = false;
    stopTimesData.value = { workdays: [], weekends: [] };
    stopTimes.value = [];

    // Function to fetch schedule data without showing 404 errors
    const fetchSchedule = async (type) => {
        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 5000);

            const response = await fetch(
                `/stop/times/${selectedStop.value.stop_id}?type=${type}&route_id=${routedata.value.route_id}&trip_id=${selectedTrip.value.trip_id}`,
                { signal: controller.signal }
            ).finally(() => clearTimeout(timeoutId));

            // Return empty array for 404 responses
            if (response.status === 404) return [];
            if (!response.ok) return [];

            const data = await response.json();
            return Array.isArray(data) ? data : [];
        } catch (error) {
            // Ignore all errors including AbortError and NetworkError
            return [];
        }
    };

    // Fetch both schedule types in parallel
    const [workdaysData, weekendsData] = await Promise.all([
        fetchSchedule('workdays'),
        fetchSchedule('weekends')
    ]);

    // Process workdays data
    stopTimesData.value.workdays = workdaysData
        .filter(time => time?.departure_time)
        .map(time => ({
            departure_time: formatTime(time.departure_time),
            isFuture: isFutureTime(formatTime(time.departure_time))
        }));
    hasWorkdaysData.value = stopTimesData.value.workdays.length > 0;

    // Process weekends data
    stopTimesData.value.weekends = weekendsData
        .filter(time => time?.departure_time)
        .map(time => ({
            departure_time: formatTime(time.departure_time),
            isFuture: isFutureTime(formatTime(time.departure_time))
        }));
    hasWeekendsData.value = stopTimesData.value.weekends.length > 0;

    // Set initial display based on data availability
    if (hasWorkdaysData.value) {
        showWorkdays.value = true;
        stopTimes.value = stopTimesData.value.workdays;
    } else if (hasWeekendsData.value) {
        showWorkdays.value = false;
        stopTimes.value = stopTimesData.value.weekends;
    }
};

watch([routedata, selectedTrip, selectedStop], () => {
    if (selectedStop.value && selectedTrip.value) {
        fetchStopTimes();
    }
});

const handleTimeClick = (time) => {
    if (!selectedTrip.value || !selectedTrip.value.trip_id || !selectedStop.value || !time.departure_time) {
        console.error(t('missingDataForTimeClick'));
        return;
    }
    router.visit(`/stoptimes?trip_id=${selectedTrip.value.trip_id}&stop_id=${selectedStop.value.stop_id}&departure_time=${time.departure_time}`);
};

const viewRouteOnMap = () => {
    if (!selectedTrip.value || !selectedTrip.value.trip_id) {
        console.error(t('missingDataForMapView'));
        return;
    }
    router.visit(`/route/map/${routedata.value.route_id}/${selectedTrip.value.trip_id}`);
};

const getTransportColor = (transportType) => {
    switch (transportType) {
        case 'bus': return '#DCA223';
        case 'trolleybus': return '#008DCA';
        case 'tram': return '#E6000B';
        default: return '#3490dc';
    }
};

const isActive = (routeName) => {
    return page.url.startsWith(routeName);
};

const getTransportTypeFromRouteId = (routeId) => {
    if (routeId.includes('bus')) return 'bus';
    if (routeId.includes('trol')) return 'trolleybus';
    if (routeId.includes('tram')) return 'tram';
    return null;
};

const updateTime = () => {
    currentTime.value = new Date();
    stopTimes.value = stopTimes.value.map(time => ({
        ...time,
        isFuture: isFutureTime(time.departure_time)
    }));
};
let timeInterval;
onMounted(() => {
    timeInterval = setInterval(updateTime, 1000);
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        currentTheme.value = savedTheme;
        document.querySelector('html').setAttribute('data-theme', savedTheme);
    }
    const savedLanguage = localStorage.getItem('language');
    if (savedLanguage) {
        locale.value = savedLanguage;
    }
});
onUnmounted(() => {
    clearInterval(timeInterval);
});
</script>

<template>
    <Head :title="routedata.route_short_name" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="/images/logo.webp">

    <header class="navbar bg-base-100">
        <div class="navbar-start">
            <a href="/">
                <button class="btn btn-square btn-ghost">
                    <img src="/images/logo.png" :alt="t('logoAlt')" class="h-10">
                </button>
            </a>
        </div>
        <div class="navbar-center">
            <div class="navbar bg-base-100">
                <a
                    href="/bus"
                    :class="['btn btn-ghost text-xl', isActive('/bus') ? 'text-white' : '']"
                    :style="{
                        backgroundColor:
                            getTransportTypeFromRouteId(routedata.route_id) === 'bus'
                                ? getTransportColor('bus')
                                : (isActive('/bus') ? getTransportColor('bus') : '')
                    }"
                >
                    {{ t('bus') }}
                </a>
            </div>
            <div class="navbar bg-base-100">
                <a
                    href="/trolleybus"
                    :class="['btn btn-ghost text-xl', isActive('/trolleybus') ? 'text-white' : '']"
                    :style="{
                        backgroundColor:
                            getTransportTypeFromRouteId(routedata.route_id) === 'trolleybus'
                                ? getTransportColor('trolleybus')
                                : (isActive('/trolleybus') ? getTransportColor('trolleybus') : '')
                    }"
                >
                    {{ t('trolleybus') }}
                </a>
            </div>
            <div class="navbar bg-base-100">
                <a
                    href="/tram"
                    :class="['btn btn-ghost text-xl', isActive('/tram') ? 'text-white' : '']"
                    :style="{
                        backgroundColor:
                            getTransportTypeFromRouteId(routedata.route_id) === 'tram'
                                ? getTransportColor('tram')
                                : (isActive('/tram') ? getTransportColor('tram') : '')
                    }"
                >
                    {{ t('tram') }}
                </a>
            </div>
            <div class="navbar bg-base-100">
                <a href="/train" :class="['btn btn-ghost text-xl', isActive('/train') ? 'bg-blue-500 text-white' : '']">
                    {{ t('train') }}
                </a>
            </div>
        </div>
        <div class="navbar-end">
            <div class="dropdown dropdown-end mr-2">
                <label tabindex="0" class="btn btn-ghost">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 21l5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 016-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 01-3.814-5.801" />
                    </svg>
                </label>
                <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-[1] w-32 p-2 shadow">
                    <li @click="changeLanguage('en')">
                        <a :class="{ 'bg-primary text-white': locale === 'en' }">{{ t('english') }}</a>
                    </li>
                    <li @click="changeLanguage('lv')">
                        <a :class="{ 'bg-primary text-white': locale === 'lv' }">{{ t('latvian') }}</a>
                    </li>
                </ul>
            </div>
            <button class="btn btn-ghost" @click="toggleTheme" :title="t('toggleTheme')">
                <svg v-if="currentTheme === 'light'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" />
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </button>
            <div class="dropdown dropdown-end">
                <button tabindex="0" role="button" class="btn btn-square btn-ghost" :title="t('menu')">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block h-5 w-5 stroke-current">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z">
                        </path>
                    </svg>
                </button>
                <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-[1] w-52 p-2 shadow">
                    <li v-if="!$page.props.auth.user">
                        <a :href="route('login')">{{ t('login') }}</a>
                    </li>
                    <li>
                        <Link href="/settings">{{ t('settings') }}</Link>
                    </li>
                    <li v-if="$page.props.auth.user">
                        <Link :href="route('logout')" method="post">{{ t('logout') }}</Link>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <div class="container mx-auto mt-6 p-4">
        <button @click="goBack" class="btn btn-outline mb-4 border-base-content/20 hover:border-base-content/40">
            ← {{ t('back') }}
        </button>
        <div class="flex items-center space-x-4">
            <div
                class="btn btn-square w-10 h-10 flex items-center justify-center text-white hover:brightness-90 transition rounded-md shadow text-sm font-bold"
                :style="{ backgroundColor: '#' + routedata.route_color }"
                :title="t('routeNumber')"
            >
                {{ routedata.route_short_name }}
            </div>
            <button
                @click="viewRouteOnMap"
                class="btn btn-square btn-ghost w-10 h-10 flex items-center justify-center"
                :title="t('viewRouteOnMap')"
            >
                <img src="/images/map-location-pin-svgrepo-com.svg" class="w-6 h-6" :alt="t('viewRouteOnMap')">
            </button>
            <div class="text-lg font-semibold text-base-content">
                {{ currentTime.toLocaleTimeString() }}
            </div>
        </div>
        <div class="mt-4">
            <label for="trip-select" class="block text-sm font-medium text-base-content">{{ t('selectTrip') }}</label>
            <select
                id="trip-select"
                v-model="selectedTrip"
                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-base-content/20 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-md bg-gray-100 dark:bg-gray-700 text-base-content"
                :aria-label="t('selectTrip')"
            >
                <option v-for="trip in trips" :key="trip.trip_id" :value="trip">
                    {{ trip.trip_headsign }} ({{ ('shapeId') }}: {{ trip.shape_id }})
                </option>
            </select>
        </div>
        <div class="mt-4">
            <label for="stop-select" class="block text-sm font-medium text-base-content">{{ t('selectStop') }}</label>
            <select
                id="stop-select"
                v-model="selectedStop"
                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-base-content/20 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-md bg-gray-100 dark:bg-gray-700 text-base-content"
                :aria-label="t('selectStop')"
            >
                <option v-for="stop in stops" :key="stop.stop_id" :value="stop">
                    {{ stop.stop_name }}
                </option>
            </select>
        </div>
        <div v-if="selectedStop" class="mt-6">
            <h2 class="text-lg font-semibold text-base-content">{{ t('stopTimesFor') }} {{ selectedStop.stop_name }}</h2>
            <div class="flex items-center mb-4" v-if="hasWorkdaysData || hasWeekendsData">
                <label class="mr-2 text-base-content">{{ t('show') }}:</label>
                <button
                    v-if="hasWorkdaysData"
                    @click="showWorkdays = true; stopTimes = stopTimesData.workdays"
                    :class="{'btn-primary': showWorkdays, 'btn-outline': !showWorkdays}"
                    class="btn btn-sm mr-2 border-base-content/20 hover:border-base-content/40"
                    :aria-label="t('showWorkdays')"
                >
                    {{ t('workdays') }}
                </button>
                <button
                    v-if="hasWeekendsData"
                    @click="showWorkdays = false; stopTimes = stopTimesData.weekends"
                    :class="{'btn-primary': !showWorkdays, 'btn-outline': showWorkdays}"
                    class="btn btn-sm border-base-content/20 hover:border-base-content/40"
                    :aria-label="t('showWeekends')"
                >
                    {{ t('weekends') }}
                </button>
            </div>
            <div v-if="stopTimes.length > 0" class="grid grid-cols-[repeat(auto-fill,minmax(60px,1fr))] gap-1 justify-items-center">
                <button
                    v-for="(time, index) in stopTimes"
                    :key="index"
                    class="btn btn-xs border-none bg-transparent hover:bg-primary hover:text-primary-content transition px-2 py-1 relative"
                    :class="{
                        'text-base-content': time.isFuture,
                        'text-base-content/70': !time.isFuture
                    }"
                    @click="handleTimeClick(time)"
                    :title="t('departureTime')"
                    :aria-label="t('departureAt', { time: time.departure_time })"
                >
                    {{ time.departure_time }}
                </button>
            </div>
            <div v-else class="text-base-content/70">
                <p>{{ t('noStopTimesAvailable') }}</p>
            </div>
        </div>
    </div>
</template>
