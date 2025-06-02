<script setup>
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';

const { t, locale } = useI18n();
const page = usePage();
const routes = ref(page.props.routes || []); // Keep routes for the general list at the bottom
const from = ref('');
const to = ref('');
const stations = ref([]);
const filteredFromStations = ref([]);
const filteredToStations = ref([]);
const selectedFromStation = ref(null);
const selectedToStation = ref(null);
const searchResults = ref([]);
const isLoading = ref(false);
const showFromDropdown = ref(false); // Added for dropdown visibility
const showToDropdown = ref(false);   // Added for dropdown visibility
const isLoadingStops = ref(false); // Added for initial stops loading indicator

// Fetch all stations
const fetchStations = async () => {
    try {
        isLoadingStops.value = true;
        const response = await axios.get('/api/train/stops');
        stations.value = response.data;
    } catch (error) {
        console.error('Error loading stations:', error);
    } finally {
        isLoadingStops.value = false;
    }
};

// Load all stations on mount
onMounted(() => {
    fetchStations();

    // Load language preference
    const savedLanguage = localStorage.getItem('language');
    if (savedLanguage) {
        locale.value = savedLanguage;
    }
});

// Watch for changes in from input
watch(from, (newVal) => {
    // Filter suggestions
    if (newVal.length > 1) {
        filteredFromStations.value = stations.value.filter(station =>
            station.stop_name.toLowerCase().includes(newVal.toLowerCase())
        ).slice(0, 5);
        showFromDropdown.value = filteredFromStations.value.length > 0;

        // Set selectedFromStation to the first filtered station for API calls
        if (filteredFromStations.value.length > 0) {
            selectedFromStation.value = filteredFromStations.value[0];
        } else {
            selectedFromStation.value = null; // Clear if no matches
        }
    } else {
        showFromDropdown.value = false;
        selectedFromStation.value = null; // Clear selected station if input is too short
    }
    // Clear 'to' selection and suggestions when 'from' changes
    to.value = '';
    selectedToStation.value = null;
    filteredToStations.value = [];
    showToDropdown.value = false;
});

// Watch for changes in to input
watch(to, async (newVal) => {
    if (newVal.length > 1) {
        // Filter from all stations since we don't have a specific train destinations endpoint
        filteredToStations.value = stations.value.filter(station =>
            station.stop_name.toLowerCase().includes(newVal.toLowerCase()) &&
            (!selectedFromStation.value || station.stop_id !== selectedFromStation.value.stop_id)
        ).slice(0, 5);
        showToDropdown.value = filteredToStations.value.length > 0;
    } else {
        filteredToStations.value = [];
        showToDropdown.value = false;
    }
});

// Select a station from the from dropdown
const selectFromStop = (station) => {
    from.value = station.stop_name;
    selectedFromStation.value = station;
    showFromDropdown.value = false;
    // Clear the 'to' field when selecting a new 'from' station
    to.value = '';
    selectedToStation.value = null;
    filteredToStations.value = [];
    // Unfocus the input
    document.activeElement.blur();
};

// Select a station from the to dropdown
const selectToStop = (station) => {
    to.value = station.stop_name;
    selectedToStation.value = station;
    showToDropdown.value = false;
    // Unfocus the input
    document.activeElement.blur();
};

// Switch from and to values
const switchStations = () => {
    const tempFromValue = from.value;
    const tempToValue = to.value;
    const tempSelectedFrom = selectedFromStation.value;
    const tempSelectedTo = selectedToStation.value;

    from.value = tempToValue;
    to.value = tempFromValue;
    selectedFromStation.value = tempSelectedTo;
    selectedToStation.value = tempSelectedFrom;

    // Re-trigger filtering logic for inputs
    // This will implicitly update filteredFromStations and filteredToStations via watchers
};

// Search for routes
const searchRoute = () => {
    if (!selectedFromStation.value || !selectedToStation.value) {
        alert(t('pleaseSelectStations'));
        return;
    }

    router.post('/train/search', {
        from: from.value,
        to: to.value
    });
};

// View route details - navigates to a new page
const viewRouteDetails = (route) => {
    // Assuming the route object from searchResults already has a trip_id
    // If clicking from 'All Routes' section, you might need a default trip_id or different route
    if (route.trip_id) {
        router.visit(`/train/details/${route.route_id}/${route.trip_id}`);
    } else {
        // Fallback for general routes without a specific trip_id if needed
        // For example, if /train/details/{route_id} exists to show all trips for a route
        // router.visit(`/train/details/${route.route_id}`);
        alert(t('noTripIdAvailable')); // Or handle this case appropriately
    }
};

const sortedRoutes = computed(() =>
    [...routes.value].sort((a, b) => {
        const aNum = parseInt(a.route_short_name) || 0;
        const bNum = parseInt(b.route_short_name) || 0;
        return aNum - bNum;
    })
);

const isActive = (routeName) => {
    return page.url.startsWith(routeName);
};

// The routeDetailsUrl function is used by router-link in the header for transport types.
// It's not directly used for route/trip details in this component's main content anymore.
const routeDetailsUrl = (routeId) => route('route.details', { route_id: routeId });

// Get color based on transport type
const getTransportColor = (transportType) => {
    switch(transportType) {
        case 'bus': return '#DCA223';
        case 'trolleybus': return '#008DCA';
        case 'tram': return '#E6000B';
        case 'train': return '#4B5563';
        default: return '#3490dc';
    }
};

// Theme Management
const currentTheme = ref('light');

// Load the theme from localStorage on initialization
onMounted(() => {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        currentTheme.value = savedTheme;
        document.querySelector('html').setAttribute('data-theme', savedTheme);
    }
});

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

// Add this helper function near the top with other functions
const formatTime = (time) => {
    if (!time) return '';
    // Handle times that might be in 24+ hour format (e.g. "25:30:00")
    const [hours, minutes] = time.split(':');
    const adjustedHours = parseInt(hours) % 24;
    return `${adjustedHours.toString().padStart(2, '0')}:${minutes}`;
};
</script>

<template>
    <Head :title="t('train')" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="/images/logo.png">

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
                <a href="/train" :class="['btn btn-ghost text-xl', isActive('/train') ? 'text-white' : '']"
                   :style="isActive('/train') ? { backgroundColor: getTransportColor('train') } : {}">
                    {{ t('train') }}
                </a>
            </div>
            <div class="navbar bg-base-100">
                <a href="/news" :class="['btn btn-ghost text-xl', isActive('/news') ? 'text-white' : '']"
                   :style="isActive('/news') ? { backgroundColor: '#4A5568' } : {}">
                    {{ t('news') }}
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

    <div class="middle" style="display: flex; flex-direction: column; align-items: center; padding-top: 20px; gap: 20px;">
        <h1 style="font-size: 2em; font-weight: bold;">{{ t('publicTransport') }}</h1>

        <div class="flex items-center gap-4">
            <div class="flex flex-col gap-4">
                <div class="relative">
                    <input
                        type="text"
                        v-model="from"
                        :placeholder="t('from')"
                        class="input input-ghost w-full max-w-xs"
                        style="border-bottom: 2px solid black;"
                        @focus="showFromDropdown = filteredFromStations.length > 0"
                        @blur="showFromDropdown = false"
                    />
                    <ul
                        v-if="showFromDropdown && filteredFromStations.length"
                        class="absolute z-10 mt-1 w-full max-w-xs bg-white dark:bg-gray-800 shadow-lg rounded-md border border-gray-200 dark:border-gray-700 menu"
                    >
                        <li v-for="station in filteredFromStations" :key="station.stop_id">
                            <a @mousedown.prevent="selectFromStop(station)">{{ station.stop_name }}</a>
                        </li>
                    </ul>
                    <div v-if="isLoadingStops" class="absolute right-3 top-3">
                        <span class="loading loading-spinner loading-xs"></span>
                    </div>
                </div>

                <div class="relative">
                    <input
                        type="text"
                        v-model="to"
                        :placeholder="t('to')"
                        class="input input-ghost w-full max-w-xs"
                        style="border-bottom: 2px solid black;"
                        @focus="showToDropdown = filteredToStations.length > 0"
                        @blur="showToDropdown = false"
                    />
                    <ul
                        v-if="showToDropdown && filteredToStations.length"
                        class="absolute z-10 mt-1 w-full max-w-xs bg-white dark:bg-gray-800 shadow-lg rounded-md border border-gray-200 dark:border-gray-700 menu"
                    >
                        <li v-for="station in filteredToStations" :key="station.stop_id">
                            <a @mousedown.prevent="selectToStop(station)">{{ station.stop_name }}</a>
                        </li>
                    </ul>
                    <div v-if="isLoadingStops" class="absolute right-3 top-3">
                        <span class="loading loading-spinner loading-xs"></span>
                    </div>
                </div>
            </div>

            <button @click="switchStations" class="btn btn-ghost p-2 self-center" :title="t('switchStations')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 transform rotate-90">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                </svg>
            </button>
        </div>

        <button
            @click="searchRoute"
            class="btn btn-primary mt-4"
            :disabled="!selectedFromStation || !selectedToStation"
        >
            {{ t('search') }}
        </button>

        <div class="w-full max-w-xl mt-6">
            <h2 class="text-xl font-bold mb-4">{{ t('allRoutes') }}</h2>
            <div class="space-y-2">
                <template v-if="sortedRoutes.length">
                    <div
                        v-for="route in sortedRoutes"
                        :key="route.route_id"
                        @click="viewRouteDetails(route)"
                        :title="route.route_long_name"
                        class="card bg-base-100 shadow hover:shadow-lg transition-shadow cursor-pointer p-4">
                        <div class="text-lg">{{ route.from_station }} → {{ route.to_station }}</div>
                    </div>
                </template>
                <p v-else class="text-gray-500">{{ t('noRoutes') }}</p>
            </div>
        </div>

        </div>
</template>

<style scoped>
.menu {
    max-height: 200px;
    overflow-y: auto;
}
</style>
