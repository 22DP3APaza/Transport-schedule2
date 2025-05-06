<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePage, router, Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();
const page = usePage();
const props = page.props;

const trips = ref(props.trips || {});
const initialTripId = ref(props.initialTripId || '');
const selectedStopId = ref(props.selectedStopId || '');
const selectedDepartureTime = ref(props.selectedDepartureTime || '');
const routeId = ref(props.routeId || '');
const error = ref(props.error || '');

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

// Load preferences on initialization
onMounted(() => {
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

// Get color based on transport type
const getTransportColor = (transportType) => {
    switch(transportType) {
        case 'bus': return '#DCA223';
        case 'trolleybus': return '#008DCA';
        case 'tram': return '#E6000B';
        default: return '#3490dc';
    }
};

// Check if route is active
const isActive = (routeName) => {
    return page.url.startsWith(routeName);
};

const matchingTrips = computed(() => {
    return Object.entries(trips.value).filter(([tripId, stops]) => {
        return stops.some(stop =>
            stop.stop_id === selectedStopId.value &&
            stop.departure_time.startsWith(selectedDepartureTime.value)
        );
    });
});

const goBack = () => {
    router.visit(`/route/details/${routeId.value}`);
};

const getTransportTypeFromRouteId = (routeId) => {
    if (routeId.includes('bus')) return 'bus';
    if (routeId.includes('trol')) return 'trolleybus';
    if (routeId.includes('tram')) return 'tram';
    return null;
};
</script>

<template>
    <Head :title="t('stopTimes')" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="/images/logo.png">

    <!-- Header Section -->
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
        <button @click="goBack" class="btn btn-outline mb-4">← {{ t('back') }}</button>

        <div v-if="error" class="alert alert-error mb-4">
            {{ error }}
        </div>

        <div class="space-y-6">
            <div v-for="[tripId, stops] in matchingTrips" :key="tripId" class="bg-base-100 p-4 rounded-lg border">
                <h2 class="text-lg font-semibold mb-2">{{ t('tripSchedule') }}</h2>

                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead>
                            <tr>
                                <th>{{ t('stopName') }}</th>
                                <th>{{ t('departureTime') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="stop in stops"
                                :key="`${tripId}-${stop.stop_sequence}`"
                                :class="{
                                    'bg-primary text-primary-content': stop.stop_id === selectedStopId && stop.departure_time.startsWith(selectedDepartureTime)
                                }"
                            >
                                <td>{{ stop.stop_name }}</td>
                                <td>{{ stop.departure_time }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div v-if="matchingTrips.length === 0 && !error" class="text-center text-gray-500 mt-8">
            <p>{{ t('noMatchingTrips') }}</p>
        </div>
    </div>
</template>
