<script setup>
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';

const { t, locale } = useI18n();
const page = usePage();
const routes = ref(page.props.routes || []);
const from = ref('');
const to = ref('');
const stations = ref([]);
const filteredFromStations = ref([]);
const filteredToStations = ref([]);
const selectedFromStation = ref(null);
const selectedToStation = ref(null);
const searchResults = ref([]);
const isLoading = ref(false);
const selectedRoute = ref(null);
const routeDetails = ref(null);

// Load all stations
onMounted(async () => {
    try {
        const response = await axios.get('/api/train/stops');
        stations.value = response.data;
    } catch (error) {
        console.error('Error loading stations:', error);
    }
});

// Filter stations based on input
const filterStations = (input, type) => {
    if (!input) {
        if (type === 'from') filteredFromStations.value = [];
        else filteredToStations.value = [];
        return;
    }

    const filtered = stations.value.filter(station =>
        station.stop_name.toLowerCase().includes(input.toLowerCase())
    );

    if (type === 'from') {
        filteredFromStations.value = filtered;
    } else {
        filteredToStations.value = filtered;
    }
};

// Watch for changes in from/to inputs
const onFromInput = (e) => {
    from.value = e.target.value;
    filterStations(from.value, 'from');
};

const onToInput = (e) => {
    to.value = e.target.value;
    filterStations(to.value, 'to');
};

// Select station
const selectStation = (station, type) => {
    if (type === 'from') {
        from.value = station.stop_name;
        selectedFromStation.value = station;
        filteredFromStations.value = [];
    } else {
        to.value = station.stop_name;
        selectedToStation.value = station;
        filteredToStations.value = [];
    }
};

// Search for routes
const searchRoute = async () => {
    if (!selectedFromStation.value || !selectedToStation.value) {
        alert(t('pleaseSelectStations'));
        return;
    }

    isLoading.value = true;
    try {
        const response = await axios.get(`/api/train/search-route/${selectedFromStation.value.stop_id}/${selectedToStation.value.stop_id}`);
        searchResults.value = response.data;
    } catch (error) {
        console.error('Error searching routes:', error);
    }
    isLoading.value = false;
};

// View route details
const viewRouteDetails = (route) => {
    router.visit(`/train/details/${route.route_id}/${route.trip_id}`);
};

// Sort the routes
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

    // Load language preference
    const savedLanguage = localStorage.getItem('language');
    if (savedLanguage) {
        locale.value = savedLanguage;
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
        <h1 style="font-size: 2em; font-weight: bold;">{{ t('trains') }}</h1>

        <div class="relative w-full max-w-xs">
            <input
                type="text"
                v-model="from"
                @input="onFromInput"
                :placeholder="t('from')"
                class="input input-ghost w-full"
                style="border-bottom: 2px solid black;"
            />
            <div v-if="filteredFromStations.length" class="absolute z-10 w-full bg-base-100 shadow-lg rounded-lg mt-1">
                <ul class="menu">
                    <li v-for="station in filteredFromStations" :key="station.stop_id">
                        <a @click="selectStation(station, 'from')">{{ station.stop_name }}</a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="relative w-full max-w-xs">
            <input
                type="text"
                v-model="to"
                @input="onToInput"
                :placeholder="t('to')"
                class="input input-ghost w-full"
                style="border-bottom: 2px solid black;"
            />
            <div v-if="filteredToStations.length" class="absolute z-10 w-full bg-base-100 shadow-lg rounded-lg mt-1">
                <ul class="menu">
                    <li v-for="station in filteredToStations" :key="station.stop_id">
                        <a @click="selectStation(station, 'to')">{{ station.stop_name }}</a>
                    </li>
                </ul>
            </div>
        </div>

        <button
            @click="searchRoute"
            class="btn btn-primary mt-4"
            :disabled="isLoading"
        >
            <span v-if="isLoading" class="loading loading-spinner"></span>
            {{ t('search') }}
        </button>

        <!-- Search Results -->
        <div v-if="searchResults.length" class="w-full max-w-xl mt-6">
            <div v-for="trip in searchResults" :key="trip.trip_id" class="card bg-base-100 shadow-xl mb-4">
                <div class="card-body">
                    <h2 class="card-title">{{ trip.route_short_name }} - {{ trip.route_long_name }}</h2>
                    <div class="flex justify-between">
                        <div>
                            <p>{{ t('departure') }}: {{ trip.departure }}</p>
                            <p>{{ t('arrival') }}: {{ trip.arrival }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- All Routes -->
        <div class="w-full max-w-xl mt-6">
            <h2 class="text-xl font-bold mb-4">{{ t('allRoutes') }}</h2>
            <div class="space-y-2">
                <div v-for="route in routes" :key="route.route_id"
                    class="card bg-base-100 shadow hover:shadow-lg transition-shadow cursor-pointer p-4"
                    @click="viewRouteDetails(route)">
                    <div class="text-lg">{{ route.from_station }} → {{ route.to_station }}</div>
                </div>
            </div>
        </div>

        <!-- Route Details Modal -->
        <div v-if="routeDetails" class="modal modal-open">
            <div class="modal-box">
                <h3 class="font-bold text-lg">{{ selectedRoute?.from_station }} → {{ selectedRoute?.to_station }}</h3>
                <div class="py-4">
                    <div v-for="stop in routeDetails.stops" :key="stop.stop_id" class="mb-2">
                        <div class="font-semibold">{{ stop.stop_name }}</div>
                        <div class="text-sm">
                            {{ t('arrival') }}: {{ stop.arrival_time }}
                            {{ t('departure') }}: {{ stop.departure_time }}
                        </div>
                    </div>
                </div>
                <div class="modal-action">
                    <button class="btn" @click="routeDetails = null">{{ t('close') }}</button>
                </div>
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
