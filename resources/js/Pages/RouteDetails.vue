<script setup>
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { ref, watch, onMounted, onUnmounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { vClickOutside } from '@/Directives/clickOutside';

const { t, locale } = useI18n();
const page = usePage();

// === Initial State ===
const routedata = ref(page.props.route || {});
const trips = ref(page.props.trips || []);
const selectedTrip = ref(page.props.selectedTrip || trips.value[0] || null);
const stops = ref(page.props.stops || []);
const selectedStop = ref(stops.value.length > 0 ? stops.value[0] : null);
const stopTimes = ref([]);
const stopTimesData = ref({ workdays: [], weekends: [] });
const hasWorkdaysData = ref(false);
const hasWeekendsData = ref(false);
const showWorkdays = ref(true);
const selectedTimes = ref([]);
const showSaveModal = ref(false);
const currentTime = ref(new Date());
const currentTheme = ref('light');
const showTableView = ref(false);

// Add directive
const directives = {
    'click-outside': vClickOutside,
};

// Add new computed properties for table data
const workdayTableData = computed(() => {
    if (!stopTimesData.value.workdays.length) return {
        hours: [],
        minutesByHour: {}
    };

    const hourlyTimes = {};
    let minHour = 24;
    let maxHour = 0;

    stopTimesData.value.workdays.forEach(time => {
        const [hours, minutes] = time.departure_time.split(':');
        const hour = parseInt(hours);
        minHour = Math.min(minHour, hour);
        maxHour = Math.max(maxHour, hour);

        if (!hourlyTimes[hour]) {
            hourlyTimes[hour] = [];
        }
        hourlyTimes[hour].push(minutes);
    });

    // Create array of hours from min to max
    const hours = Array.from({ length: maxHour - minHour + 1 }, (_, i) => minHour + i);

    return {
        hours,
        minutesByHour: hourlyTimes
    };
});

const weekendTableData = computed(() => {
    if (!stopTimesData.value.weekends.length) return {
        hours: [],
        minutesByHour: {}
    };

    const hourlyTimes = {};
    let minHour = 24;
    let maxHour = 0;

    stopTimesData.value.weekends.forEach(time => {
        const [hours, minutes] = time.departure_time.split(':');
        const hour = parseInt(hours);
        minHour = Math.min(minHour, hour);
        maxHour = Math.max(maxHour, hour);

        if (!hourlyTimes[hour]) {
            hourlyTimes[hour] = [];
        }
        hourlyTimes[hour].push(minutes);
    });

    // Create array of hours from min to max
    const hours = Array.from({ length: maxHour - minHour + 1 }, (_, i) => minHour + i);

    return {
        hours,
        minutesByHour: hourlyTimes
    };
});

const routeDisplayName = computed(() => {
    if (stops.value.length > 0) {
        const firstStopName = stops.value[0]?.stop_name || '';
        const lastStopName = stops.value[stops.value.length - 1]?.stop_name || '';
        return `${firstStopName} - ${lastStopName}`;
    }
    return routedata.value.route_short_name || '';
});

// === Theme Management ===
const toggleTheme = () => {
    currentTheme.value = currentTheme.value === 'light' ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', currentTheme.value);
    localStorage.setItem('theme', currentTheme.value);
};

// === Language Management ===
const changeLanguage = (language) => {
    locale.value = language;
    localStorage.setItem('language', language);
};

// === Navigation ===
const goBack = () => window.history.back();

const viewRouteOnMap = () => {
    if (!selectedTrip.value?.trip_id) {
        console.error(t('missingDataForMapView'));
        return;
    }
    router.visit(`/route/map/${routedata.value.route_id}/${selectedTrip.value.trip_id}`);
};


const handleTimeClick = (time) => {
    if (!selectedStop.value || !time.departure_time || !time.trip_id) {
        console.error(t('missingDataForTimeClick'), time);
        return;
    }

    // Pass the specific trip_id along with stop_id and departure_time
    router.visit(`/stoptimes?trip_id=${time.trip_id}&stop_id=${selectedStop.value.stop_id}&departure_time=${time.departure_time}`, {
        onError: (errors) => {

            console.error(`${t('errorNavigating')}:`, errors);
            alert(`${t('errorNavigating')}: ${errors.message || 'Unknown error'}`);
        }
    });
};

// === Format and Time Checks ===
const formatTime = (time) => {
    if (!time) return '';
    return time.split(':').slice(0, 2).join(':');
};

const isFutureTime = (timeStr) => {
    const now = currentTime.value;
    const [hours, minutes] = timeStr.split(':').map(Number);
    return hours > now.getHours() || (hours === now.getHours() && minutes > now.getMinutes());
};

// --- New Function: Fetch User's Saved Times ---
const fetchUserSavedTimes = async () => {
    if (!page.props.auth.user || !selectedTrip.value || !selectedStop.value) {
        selectedTimes.value = []; // Clear selected times if not logged in or data is missing
        return;
    }

    try {
        const response = await fetch(`/my-saved-times?trip_id=${selectedTrip.value.trip_id}&stop_id=${selectedStop.value.stop_id}`);
        if (response.ok) {
            const data = await response.json();
            // Find the record for the current trip_id and stop_id
            const savedRecord = data.find(
                record => record.trip_id === selectedTrip.value.trip_id && record.stop_id === selectedStop.value.stop_id
            );
            // Populate selectedTimes with the saved times if found, otherwise clear it
            selectedTimes.value = savedRecord ? savedRecord.saved_times : [];
        } else {
            console.error('Error fetching user saved times:', response.statusText);
            selectedTimes.value = [];
        }
    } catch (error) {
        console.error('Error fetching user saved times:', error);
        selectedTimes.value = [];
    }
};

// === Save Times ===
const saveSelectedTimes = async () => {
    // Only allow saving if user is logged in
    if (!page.props.auth.user) {
        console.warn(t('loginToSaveTimes'));
        showSaveModal.value = false;
        return;
    }

    // Ensures selectedTimes is not empty
    if (!selectedTrip.value || !selectedStop.value || selectedTimes.value.length === 0) {
        console.warn(t('selectTimesFirst'));
        showSaveModal.value = false;
        return;
    }

    try {
        const response = await fetch('/save-stop-times', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({
                trip_id: selectedTrip.value.trip_id,
                stop_id: selectedStop.value.stop_id,
                times: selectedTimes.value // This is the array of selected departure times
            })
        });

        if (response.ok) {
            console.log(t('timesSavedSuccessfully'));
            fetchUserSavedTimes();
            showSaveModal.value = false;
        } else {
            const errorData = await response.json();
            console.error(t('errorSavingTimes'), errorData);
            alert(`${t('errorSavingTimes')}: ${errorData.message || response.statusText}`);
        }
    } catch (error) {
        console.error(error);
        console.error(t('errorSavingTimes'));
        alert(`${t('errorSavingTimes')}: ${error.message}`);
    }
};


// === Fetch Stop Times ===
const fetchStopTimes = async () => {
    if (!selectedStop.value || !selectedTrip.value) return;

    hasWorkdaysData.value = false;
    hasWeekendsData.value = false;
    stopTimesData.value = { workdays: [], weekends: [] };
    stopTimes.value = []; // Clear current stopTimes

    const fetchSchedule = async (type) => {
        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 5000);

            // Pass the selectedTrip.value.trip_id to the backend
            const response = await fetch(
                `/stop/times/${selectedStop.value.stop_id}?type=${type}&route_id=${routedata.value.route_id}&trip_id=${selectedTrip.value.trip_id}`,
                { signal: controller.signal }
            ).finally(() => clearTimeout(timeoutId));

            if (!response.ok) {
                console.warn(`Failed to fetch ${type} schedule for stop ${selectedStop.value.stop_id} and route ${routedata.value.route_id}. Status: ${response.status}`);
                return [];
            }

            const data = await response.json();
            // Ensures data is an array before mapping
            return Array.isArray(data) ? data : [];
        } catch (error) {
            console.error(`Error fetching ${type} schedule:`, error);
            // Return empty array on error to prevent rendering issues
            return [];
        }
    };

    const [workdaysData, weekendsData] = await Promise.all([
        fetchSchedule('workdays'),
        fetchSchedule('weekends')
    ]);

    // Process fetched data
    stopTimesData.value.workdays = workdaysData
        .filter(t => t?.departure_time && t?.trip_id) // Ensures both are present
        .map(t => ({
            departure_time: formatTime(t.departure_time),
            trip_id: t.trip_id,
            isFuture: isFutureTime(formatTime(t.departure_time))
        }));
    hasWorkdaysData.value = stopTimesData.value.workdays.length > 0;

    stopTimesData.value.weekends = weekendsData
        .filter(t => t?.departure_time && t?.trip_id) // Ensures both are present
        .map(t => ({
            departure_time: formatTime(t.departure_time),
            trip_id: t.trip_id,
            isFuture: isFutureTime(formatTime(t.departure_time))
        }));
    hasWeekendsData.value = stopTimesData.value.weekends.length > 0;

    if (hasWorkdaysData.value) {
        showWorkdays.value = true;
        stopTimes.value = stopTimesData.value.workdays;
    } else if (hasWeekendsData.value) {
        showWorkdays.value = false;
        stopTimes.value = stopTimesData.value.weekends;
    } else {
        // If neither has data, ensure stopTimes is empty and no tabs are shown
        stopTimes.value = [];
        showWorkdays.value = true; // Default to workdays tab, but it will be empty
    }
};

// === Watchers and Lifecycle Hooks ===
watch(selectedTrip, async (newTrip) => {
    if (!newTrip) {
        stops.value = [];
        selectedStop.value = null;
        return;
    }

    try {
        const res = await fetch(`/route/details/${routedata.value.route_id}/${newTrip.trip_id}/stops`);
        const data = await res.json();
        stops.value = data;
        selectedStop.value = data[0] || null;
    } catch (err) {
        console.error(t('errorFetchingStops'), err);
        stops.value = [];
        selectedStop.value = null;
    }
}, { immediate: true });

// This watcher triggers fetching stop times AND user's saved times
watch([selectedStop, selectedTrip], () => {
    if (selectedStop.value && selectedTrip.value) {
        fetchStopTimes();
        fetchUserSavedTimes(); // Fetch user's saved times whenever stop/trip changes
    } else {
        // Clear stop times if selection is invalid
        stopTimes.value = [];
        stopTimesData.value = { workdays: [], weekends: [] };
        hasWorkdaysData.value = false;
        hasWeekendsData.value = false;
    }
});

// Watch showSaveModal to ensure selectedTimes is updated when modal opens
watch(showSaveModal, (newValue) => {
    if (newValue) {
        fetchUserSavedTimes(); // Refresh saved times when modal opens
    } else {
        selectedTimes.value = [];
    }
});


const updateTime = () => {
    currentTime.value = new Date();
    // Re-evaluate isFuture for all stop times
    stopTimes.value = stopTimes.value.map(t => ({
        ...t,
        isFuture: isFutureTime(t.departure_time)
    }));
};

let timeInterval = null;
onMounted(() => {
    timeInterval = setInterval(updateTime, 1000); // Update every second instead of every minute
    updateTime(); // Initial update

    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        currentTheme.value = savedTheme;
        document.documentElement.setAttribute('data-theme', savedTheme);
    } else {
        document.documentElement.setAttribute('data-theme', 'light');
    }

    const savedLang = localStorage.getItem('language');
    if (savedLang) {
        locale.value = savedLang;
    }
});

onUnmounted(() => {
    clearInterval(timeInterval);
});

// === Helper Functions (No changes needed here) ===
const getTransportColor = (type) => {
    return {
        bus: '#DCA223',
        trolleybus: '#008DCA',
        tram: '#E6000B'
    }[type] || '#3490dc';
};

const isActive = (routeName) => page.url.startsWith(routeName);

const getTransportTypeFromRouteId = (id) => {
    if (id.includes('bus')) return 'bus';
    if (id.includes('trol')) return 'trolleybus';
    if (id.includes('tram')) return 'tram';
    return null;
};
</script>

<template>
    <Head :title="routeDisplayName" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="/images/logo.webp">

    <header class="navbar bg-base-100">
        <div class="navbar-start">
            <a href="/">
                <button class="btn btn-square btn-ghost">
                    <img src="/images/logo.png" alt="Logo" class="h-10">
                </button>
            </a>
        </div>
        <div class="navbar-center">
            <div class="navbar bg-base-100">
                <a href="/bus" :class="['btn btn-ghost text-xl', isActive('/bus') ? 'text-white' : '']"
                    :style="isActive('/bus') ? { backgroundColor: getTransportColor('bus') } : {}">
                    {{ t('bus') }}
                </a>
            </div>
            <div class="navbar bg-base-100">
                <a href="/trolleybus" :class="['btn btn-ghost text-xl', isActive('/trolleybus') ? 'text-white' : '']"
                    :style="isActive('/trolleybus') ? { backgroundColor: getTransportColor('trolleybus') } : {}">
                    {{ t('trolleybus') }}
                </a>
            </div>
            <div class="navbar bg-base-100">
                <a href="/tram" :class="['btn btn-ghost text-xl', isActive('/tram') ? 'text-white' : '']"
                    :style="isActive('/tram') ? { backgroundColor: getTransportColor('tram') } : {}">
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
            <button class="btn btn-ghost" @click="toggleTheme">
                <svg v-if="currentTheme === 'light'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" />
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </button>

            <div class="dropdown dropdown-end">
                <button tabindex="0" role="button" class="btn btn-square btn-ghost">
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
                    <li v-if="$page.props.auth.user">
                        <Link href="/settings">{{ t('settings') }}</Link>
                    </li>
                    <li v-if="$page.props.auth.user?.admin">
                        <Link href="/admin/users">{{ t('adminPanel') }}</Link>
                    </li>
                    <li v-if="$page.props.auth.user">
                        <Link :href="route('logout')" method="post">{{ t('logout') }}</Link>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <div class="container mx-auto mt-6 p-4">
        <div class="flex justify-between items-center mb-6">
            <button @click="goBack" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded inline-flex items-center">
                <span>← {{ t('back') }}</span>
            </button>
            <div class="flex space-x-2">
                <button @click="viewRouteOnMap" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded inline-flex items-center">
                    <span>{{ t('viewOnMap') }}</span>
                </button>
                <a
                    v-if="selectedStop"
                    :href="`/route/details/${routedata.route_id}/${selectedStop.stop_id}/pdf?lang=${locale}`"
                    class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded inline-flex items-center"
                    target="_blank"
                >
                    <span>{{ t('downloadPDF') }}</span>
                </a>
            </div>
        </div>

        <div class="flex items-center space-x-4">
            <div
                class="btn btn-square w-auto h-10 px-4 flex items-center justify-center text-white hover:brightness-90 transition rounded-md shadow text-sm font-bold"
                :style="{ backgroundColor: '#' + routedata.route_color }"
                :title="t('routeNumber')"
            >
                {{ routeDisplayName }} </div>
            <div class="text-lg font-semibold text-base-content">
                {{ currentTime.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }) }}
            </div>
        </div>

        <div class="mt-4">
            <label for="trip-select" class="block text-sm font-medium text-base-content">{{ t('selectTrip') }}</label>
            <select
                id="trip-select"
                v-model="selectedTrip"
                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-base-content/20 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-md text-base-content"
                :class="{ 'bg-gray-700': currentTheme === 'dark', 'bg-gray-100': currentTheme === 'light' }"
                :aria-label="t('selectTrip')"
            >
                <option v-for="trip in trips" :key="trip.trip_id" :value="trip">
                    {{ trip.full_name }}
                </option>
            </select>
        </div>

        <div class="mt-4">
            <label for="stop-select" class="block text-sm font-medium text-base-content">{{ t('selectStop') }}</label>
            <select
                id="stop-select"
                v-model="selectedStop"
                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-base-content/20 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-md text-base-content"
                :class="{ 'bg-gray-700': currentTheme === 'dark', 'bg-gray-100': currentTheme === 'light' }"
                :aria-label="t('selectStop')"
            >
                <option v-for="stop in stops" :key="stop.stop_id" :value="stop">
                    {{ stop.stop_name }}
                </option>
            </select>
        </div>

        <div v-if="selectedStop" class="mt-6">
            <h2 class="text-lg font-semibold text-base-content">
                {{ t('stopTimesFor') }} {{ selectedStop.stop_name }}
            </h2>

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
                <button
                    @click="showTableView = !showTableView"
                    class="btn btn-sm ml-4 border-base-content/20 hover:border-base-content/40"
                    :class="{'btn-primary': showTableView, 'btn-outline': !showTableView}"
                >
                    <img src="/images/table.svg" alt="Table view" class="w-4 h-4" :class="{'filter invert': showTableView && currentTheme === 'light'}">
                </button>
            </div>

            <!-- Grid View -->
            <div v-if="!showTableView && stopTimes.length > 0" class="grid grid-cols-[repeat(auto-fill,minmax(60px,1fr))] gap-1 justify-items-center">
                <button
                    v-for="(time, index) in stopTimes"
                    :key="`${time.trip_id}-${time.departure_time}-${index}`"
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

            <!-- Table View -->
            <div v-if="showTableView" class="overflow-x-auto">
                <table class="table table-compact w-full">
                    <thead>
                        <tr class="text-center">
                            <th v-for="hour in (showWorkdays ? workdayTableData.hours : weekendTableData.hours)"
                                :key="hour"
                                class="px-2 py-2 bg-base-200 text-base-content font-bold sticky top-0"
                            >
                                {{ hour.toString().padStart(2, '0') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td v-for="hour in (showWorkdays ? workdayTableData.hours : weekendTableData.hours)"
                                :key="hour"
                                class="p-2 border border-base-300 align-top"
                            >
                                <div class="flex flex-col items-center space-y-1">
                                    <button
                                        v-for="minute in (showWorkdays ? workdayTableData.minutesByHour[hour] : weekendTableData.minutesByHour[hour]) || []"
                                        :key="`${hour}-${minute}`"
                                        @click="handleTimeClick({
                                            departure_time: `${hour.toString().padStart(2, '0')}:${minute}`,
                                            trip_id: stopTimes.find(t => t.departure_time === `${hour.toString().padStart(2, '0')}:${minute}`)?.trip_id
                                        })"
                                        class="w-full btn btn-xs border-none bg-transparent hover:bg-primary hover:text-primary-content transition px-2 py-1"
                                        :class="{
                                            'text-base-content': isFutureTime(`${hour}:${minute}`),
                                            'text-base-content/70': !isFutureTime(`${hour}:${minute}`)
                                        }"
                                    >
                                        {{ minute }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else-if="!stopTimes.length" class="text-base-content/70">
                <p>{{ t('noStopTimesAvailable') }}</p>
            </div>

            <button
                v-if="stopTimes.length && $page.props.auth.user"
                @click="showSaveModal = true"
                class="btn btn-outline btn-sm mt-2"
            >
                {{ t('saveSelectedTimes') }}
            </button>
            <div v-else-if="stopTimes.length && !$page.props.auth.user" class="text-sm text-base-content/70 mt-2">
                    {{ t('loginToSaveTimes') }}
            </div>
        </div>

        <div
            v-if="showSaveModal"
            class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center"
            @click.self="showSaveModal = false"
        >
            <div class="bg-base-100 p-4 rounded-lg w-96 shadow-lg">
                <h3 class="text-lg font-bold mb-3">
                    {{ t('selectStopTimesToSave') }}
                </h3>

                <div class="max-h-60 overflow-y-auto grid grid-cols-4 gap-2">
                    <label
                        v-for="(time, index) in stopTimes"
                        :key="'checkbox-' + index"
                        class="flex items-center text-sm gap-1"
                    >
                        <input
                            type="checkbox"
                            :value="time.departure_time"
                            v-model="selectedTimes"
                        />
                        <span>{{ time.departure_time }}</span>
                    </label>
                </div>

                <div class="mt-4 flex justify-end gap-2">
                    <button
                        class="btn btn-sm btn-outline"
                        @click="showSaveModal = false"
                    >
                        {{ t('cancel') }}
                    </button>
                    <button
                        class="btn btn-sm btn-primary"
                        @click="saveSelectedTimes"
                    >
                        {{ t('save') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
