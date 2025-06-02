<script setup>
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';

const { t, locale } = useI18n();
const page = usePage();
const currentTheme = ref('light');
const from = ref('');
const to = ref('');
const stops = ref([]);
const filteredFromStops = ref([]);
const filteredToStops = ref([]);
const showFromDropdown = ref(false);
const showToDropdown = ref(false);
const isLoadingStops = ref(false);
const transportType = ref('bus'); // Default transport type
const showTransportDropdown = ref(false); // Control transport type dropdown visibility

// for displaying all user's saved times
const userSavedTimes = ref([]);

// Computed property to sort saved times
const sortedSavedTimes = computed(() => {
    // Create an array of individual time entries
    const expandedTimes = userSavedTimes.value.flatMap(saved =>
        saved.saved_times.map(timeData => ({
            id: `${saved.id}-${typeof timeData === 'string' ? timeData : timeData.time}`,
            route_id: saved.route_id,
            trip_id: saved.trip_id,
            stop_id: saved.stop_id,
            route_name: saved.route_name,
            stop_name: saved.stop_name,
            route_color: saved.route_color,
            time: typeof timeData === 'string' ? timeData : timeData.time,
            schedule_type: typeof timeData === 'string' ? 'workday' : timeData.schedule_type // Default to workday for old data
        }))
    );

    // Sort by time
    return expandedTimes.sort((a, b) => {
        const [aHours, aMinutes] = a.time.split(':').map(Number);
        const [bHours, bMinutes] = b.time.split(':').map(Number);
        if (aHours === bHours) {
            return aMinutes - bMinutes;
        }
        return aHours - bHours;
    });
});

const transportTypes = [
    { id: 'all', name: t('all'), color: '#3490dc' },
    { id: 'bus', name: t('bus'), color: '#DCA223' },
    { id: 'trolleybus', name: t('trolleybus'), color: '#008DCA' },
    { id: 'tram', name: t('tram'), color: '#E6000B' },
    { id: 'train', name: t('train'), color: '#4B5563' }
];

// Watch for changes in locale to update transport type names
watch(locale, () => {
    transportTypes.forEach(type => {
        type.name = t(type.id);
    });
});

// Extract stop names from route names (format: "Stop1 - Stop2")
const extractStopsFromRoutes = (routes) => {
    const routeStops = new Set(); // Use a Set to automatically handle duplicates
    routes.forEach(route => {
        if (route.route_long_name.includes(' - ')) {
            const [stop1, stop2] = route.route_long_name.split(' - ');
            routeStops.add(stop1.trim());
            routeStops.add(stop2.trim());
        }
    });
    return Array.from(routeStops).map(stop_name => ({
        stop_name,
        from_route: true
    }));
};

// Combine stops from API and routes, ensuring uniqueness by stop name
const combineStops = (apiStops, routeStops) => {
    const uniqueStops = new Map();

    // First add all API stops
    apiStops.forEach(stop => {
        uniqueStops.set(stop.stop_name.toLowerCase(), stop);
    });

    // Then only add route stops if they don't exist in API stops
    routeStops.forEach(routeStop => {
        const lowerName = routeStop.stop_name.toLowerCase();
        if (!uniqueStops.has(lowerName)) {
            uniqueStops.set(lowerName, routeStop);
        }
    });

    return Array.from(uniqueStops.values());
};

// Fetch stops data and combine with stops extracted from routes
const fetchStops = async () => {
    try {
        isLoadingStops.value = true;
        const endpoint = transportType.value === 'train' ? '/api/train/stops' : '/api/stops';
        const [stopsResponse] = await Promise.all([
            axios.get(endpoint).catch(() => ({ data: [] })),
        ]);

        // Get unique stops from API
        const apiStops = stopsResponse.data.reduce((acc, stop) => {
            if (!acc.some(s => s.stop_name.toLowerCase() === stop.stop_name.toLowerCase())) {
                acc.push(stop);
            }
            return acc;
        }, []);

        // Extract stops from route names if available and if not in train mode
        const routeStops = (page.props.routes && transportType.value !== 'train') ? extractStopsFromRoutes(page.props.routes) : [];

        // Don't combine train stops with regular stops when in 'all' mode
        if (transportType.value === 'all') {
            stops.value = apiStops.filter(stop => stop.transport_type !== 'train');
        } else {
            // Combine and deduplicate
            stops.value = combineStops(apiStops, routeStops);
        }
    } catch (error) {
        console.error('Error fetching stops:', error);
    } finally {
        isLoadingStops.value = false;
    }
};

// Watch for changes in from input
watch(from, async (newVal) => {
    if (newVal.length > 1) {
        // Search in all stops (both API and route-extracted stops)
        filteredFromStops.value = stops.value.filter(stop =>
            stop.stop_name.toLowerCase().includes(newVal.toLowerCase())
        ).slice(0, 5);
        showFromDropdown.value = filteredFromStops.value.length > 0;
    } else {
        showFromDropdown.value = false;
    }
});

// Watch for changes in to input
watch(to, async (newVal) => {
    if (newVal.length > 1 && from.value) {
        try {
            if (transportType.value === 'train') {
                // For trains, filter from the loaded stations, excluding the 'from' station
                filteredToStops.value = stops.value
                    .filter(stop =>
                        stop.stop_name.toLowerCase().includes(newVal.toLowerCase()) &&
                        stop.stop_name.toLowerCase() !== from.value.toLowerCase()
                    )
                    .slice(0, 5);
            } else {
                // For other transport types, use the API
                const response = await axios.get('/api/possible-destinations', {
                    params: {
                        from: from.value,
                        type: transportType.value
                    }
                });

                // Get stops from API response
                let possibleStops = response.data;

                // Also search in route names for potential destinations
                const routeNameStops = stops.value.filter(stop =>
                    stop.from_route &&
                    stop.stop_name.toLowerCase().includes(newVal.toLowerCase()) &&
                    stop.stop_name.toLowerCase() !== from.value.toLowerCase()
                );

                // Combine API results with route name stops, ensuring uniqueness
                const uniqueStops = new Map();

                // Add API stops first
                possibleStops.forEach(stop => {
                    uniqueStops.set(stop.stop_name.toLowerCase(), stop);
                });

                // Add route name stops if they don't exist
                routeNameStops.forEach(stop => {
                    const lowerName = stop.stop_name.toLowerCase();
                    if (!uniqueStops.has(lowerName)) {
                        uniqueStops.set(lowerName, stop);
                    }
                });

                // Convert back to array and filter by search term
                filteredToStops.value = Array.from(uniqueStops.values())
                    .filter(stop => stop.stop_name.toLowerCase().includes(newVal.toLowerCase()))
                    .slice(0, 5);
            }

            showToDropdown.value = filteredToStops.value.length > 0;
        } catch (error) {
            console.error('Error fetching possible destinations:', error);

            // Fallback to filtering from all stops
            filteredToStops.value = stops.value
                .filter(stop =>
                    stop.stop_name.toLowerCase().includes(newVal.toLowerCase()) &&
                    stop.stop_name.toLowerCase() !== from.value.toLowerCase()
                )
                .slice(0, 5);
        }
    } else {
        showToDropdown.value = false;
    }
});

// Select a stop from the from dropdown
const selectFromStop = (stop) => {
    from.value = stop.stop_name;
    showFromDropdown.value = false;
    // Clear the 'to' field when changing 'from' stop
    to.value = '';
    filteredToStops.value = [];
    // Unfocus the input
    document.activeElement.blur();
};

// Select a stop from the to dropdown
const selectToStop = (stop) => {
    to.value = stop.stop_name;
    showToDropdown.value = false;
    // Unfocus the input
    document.activeElement.blur();
};

// Switch from and to values
const switchStops = () => {
    const temp = from.value;
    from.value = to.value;
    to.value = temp;
};

// Search for routes and navigate to the appropriate transport page
const searchRoute = () => {
    if (from.value && to.value) {
        if (transportType.value === 'all') {
            // Search in all transport types
            router.post('/search-route', {
                from: from.value,
                to: to.value,
                type: 'all'
            });
        } else {
            // Search in specific transport type
            router.post(`/${transportType.value}/search`, {
                from: from.value,
                to: to.value
            });
        }
    } else {
        alert(t('pleaseEnterValues'));
    }
};

// Set transport type
const setTransportType = (type) => {
    transportType.value = type;
    showTransportDropdown.value = false;
};

const isActive = (path, type) => {
    const isActivePath = window.location.pathname === path;
    if (isActivePath) {
        setTransportType(type);
    }
    return isActivePath;
};

// Get color based on transport type
const getTransportColor = (transportType) => {
    const type = transportTypes.find(t => t.id === transportType);
    return type ? type.color : '#3490dc';
};

// Get current transport type name
const currentTransportName = computed(() => {
    const type = transportTypes.find(t => t.id === transportType.value);
    return type ? type.name : t('bus');
});

//Fetch ALL User's Saved Times for the table display ---
const fetchAllUserSavedTimes = async () => {
    if (!page.props.auth.user) {
        userSavedTimes.value = [];
        return;
    }
    try {
        const response = await fetch('/my-saved-times'); // Fetch all saved times for the user
        if (response.ok) {
            const data = await response.json();
            userSavedTimes.value = data;
        } else {
            console.error('Error fetching all user saved times:', response.statusText);
            userSavedTimes.value = [];
        }
    } catch (error) {
        console.error('Error fetching all user saved times:', error);
        userSavedTimes.value = [];
    }
};

// === Delete Saved Time ===
const deleteSavedTime = async (savedTimeId) => {
    if (!page.props.auth.user) {
        console.warn(t('loginToDeleteTimes'));
        return;
    }


    if (!confirm(t('confirmDeleteSavedTime'))) {
        return;
    }

    try {
        const response = await fetch(`/my-saved-times/${savedTimeId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            }
        });

        if (response.ok) {
            console.log(t('savedTimeDeletedSuccessfully'));
            fetchAllUserSavedTimes(); // Refresh the table data after deletion
        } else {
            // Check if the response is JSON
            const contentType = response.headers.get('Content-Type');
            let errorMessage = response.statusText;

            if (contentType && contentType.includes('application/json')) {
                const errorData = await response.json();
                errorMessage = errorData.message || response.statusText;
            } else {
                // If not JSON, read as plain text
                errorMessage = await response.text();
            }

            console.error(t('errorDeletingSavedTime'), errorMessage);

            alert(`${t('errorDeletingSavedTime')}: ${errorMessage}`);
        }
    } catch (error) {
        console.error('Error deleting saved time:', error);

        alert(`${t('errorDeletingSavedTime')}: ${error.message}`);
    }
};

//Navigate to Route Details from Saved Time
const viewSavedRoute = async (saved, selectedTime) => {
    try {
        // Convert Proxy to plain object and ensure we have all required properties
        const savedData = {
            route_id: saved?.route_id,
            trip_id: saved?.trip_id,
            stop_id: saved?.stop_id,
            selected_time: selectedTime
        };

        // Now check if we have all required data
        if (!savedData.route_id || !savedData.trip_id || !savedData.stop_id) {
            console.error('Missing route, trip, or stop ID for navigation:', savedData);
            alert(t('missingDataForNavigation'));
            return;
        }

        // Construct the URL with database parameter if it exists
        const database = page.props.database;
        const baseUrl = `/route/details/${savedData.route_id}/${savedData.trip_id}`;
        const url = database ? `${baseUrl}?database=${database}` : baseUrl;

        // Navigate to the route details page with the specific time
        router.visit(url, {
            data: {
                selected_stop_id: savedData.stop_id,
                selected_time: savedData.selected_time
            }
        });
    } catch (error) {
        console.error('Error navigating to route:', error);
        alert(t('errorNavigatingToRoute'));
    }
};

// Watch for changes in transport type to refetch stops
watch(transportType, () => {
    // Clear current selections
    from.value = '';
    to.value = '';
    filteredFromStops.value = [];
    filteredToStops.value = [];
    // Refetch stops for the new transport type
    fetchStops();
});

// Load theme, language preferences and stops data
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

    // Set initial transport type based on current route
    const path = window.location.pathname;
    if (path.includes('bus')) transportType.value = 'bus';
    else if (path.includes('trolleybus')) transportType.value = 'trolleybus';
    else if (path.includes('tram')) transportType.value = 'tram';
    else if (path.includes('train')) transportType.value = 'train';

    fetchStops();
    fetchAllUserSavedTimes(); // Fetch all user's saved times on mount
});

const toggleTheme = () => {
    currentTheme.value = currentTheme.value === 'light' ? 'dark' : 'light';
    document.querySelector('html').setAttribute('data-theme', currentTheme.value);
    localStorage.setItem('theme', currentTheme.value);
};

const changeLanguage = (language) => {
    locale.value = language;
    localStorage.setItem('language', language);
};

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
                times: selectedTimes.value,
                schedule_types: selectedTimes.value.map(() => showWorkdays ? 'workday' : 'weekend')
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
</script>

<template>
    <Head :title="t('home')">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" type="image/png" href="/images/logo.png">
    </Head>

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
                <a href="/bus" :class="['btn btn-ghost text-xl', isActive('/bus', 'bus') ? 'text-white' : '']"
                    :style="isActive('/bus', 'bus') ? { backgroundColor: getTransportColor('bus') } : {}">
                    {{ t('bus') }}
                </a>
            </div>
            <div class="navbar bg-base-100">
                <a href="/trolleybus" :class="['btn btn-ghost text-xl', isActive('/trolleybus', 'trolleybus') ? 'text-white' : '']"
                    :style="isActive('/trolleybus', 'trolleybus') ? { backgroundColor: getTransportColor('trolleybus') } : {}">
                    {{ t('trolleybus') }}
                </a>
            </div>
            <div class="navbar bg-base-100">
                <a href="/tram" :class="['btn btn-ghost text-xl', isActive('/tram', 'tram') ? 'text-white' : '']"
                    :style="isActive('/tram', 'tram') ? { backgroundColor: getTransportColor('tram') } : {}">
                    {{ t('tram') }}
                </a>
            </div>
            <div class="navbar bg-base-100">
                <a href="/train" :class="['btn btn-ghost text-xl', isActive('/train', 'train') ? 'text-white' : '']"
                    :style="isActive('/train', 'train') ? { backgroundColor: getTransportColor('train') } : {}">
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
                    <li v-if="!page.props.auth.user">
                        <a :href="route('login')">{{ t('login') }}</a>
                    </li>
                    <li v-if="page.props.auth.user">
                        <Link href="/settings">{{ t('settings') }}</Link>
                    </li>
                    <li v-if="page.props.auth.user?.admin">
                        <Link href="/admin/users">{{ t('adminPanel') }}</Link>
                    </li>
                    <li v-if="page.props.auth.user">
                        <Link :href="route('logout')" method="post">{{ t('logout') }}</Link>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <div class="middle" style="display: flex; flex-direction: column; align-items: center; padding-top: 20px; gap: 20px;">
        <h1 style="font-size: 2em; font-weight: bold;">{{ t('publicTransport') }}</h1>

        <div class="dropdown" :class="{ 'dropdown-open': showTransportDropdown }">
            <label tabindex="0" class="btn m-1"
                    @click="showTransportDropdown = !showTransportDropdown"
                    :style="{ backgroundColor: getTransportColor(transportType), color: 'white' }">
                {{ currentTransportName }}
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </label>
            <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-52 z-10">
                <li v-for="type in transportTypes" :key="type.id">
                    <a @click="setTransportType(type.id)"
                        :style="{ color: type.id === transportType ? 'white' : type.color, backgroundColor: type.id === transportType ? type.color : 'transparent' }">
                        {{ type.name }}
                    </a>
                </li>
            </ul>
        </div>

        <div class="flex items-center gap-4">
            <div class="flex flex-col gap-4">
                <div class="relative">
                    <input
                        type="text"
                        v-model="from"
                        :placeholder="t('from')"
                        class="input input-ghost w-full max-w-xs"
                        style="border-bottom: 2px solid black;"
                        @focus="showFromDropdown = filteredFromStops.length > 0"
                        @blur="showFromDropdown = false"
                    />
                    <ul
                        v-if="showFromDropdown && filteredFromStops.length"
                        class="absolute z-10 mt-1 w-full max-w-xs bg-white dark:bg-gray-800 shadow-lg rounded-md border border-gray-200 dark:border-gray-700"
                    >
                        <li
                            v-for="stop in filteredFromStops"
                            :key="stop.stop_id || stop.stop_name"
                            @mousedown.prevent="selectFromStop(stop)"
                            class="px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer"
                        >
                            {{ stop.stop_name }}
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
                        @focus="showToDropdown = filteredToStops.length > 0"
                        @blur="showToDropdown = false"
                    />
                    <ul
                        v-if="showToDropdown && filteredToStops.length"
                        class="absolute z-10 mt-1 w-full max-w-xs bg-white dark:bg-gray-800 shadow-lg rounded-md border border-gray-200 dark:border-gray-700"
                    >
                        <li
                            v-for="stop in filteredToStops"
                            :key="stop.stop_id || stop.stop_name"
                            @mousedown.prevent="selectToStop(stop)"
                            class="px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer"
                        >
                            {{ stop.stop_name }}
                        </li>
                    </ul>
                    <div v-if="isLoadingStops" class="absolute right-3 top-3">
                        <span class="loading loading-spinner loading-xs"></span>
                    </div>
                </div>
            </div>

            <button @click="switchStops" class="btn btn-ghost p-2 self-center" :title="t('switchStops')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 transform rotate-90">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                </svg>
            </button>
        </div>

        <button
            @click="searchRoute"
            class="btn mt-4"
            :style="{ backgroundColor: getTransportColor(transportType) }"
        >
            {{ t('search') }}
        </button>
    </div>

    <div v-if="$page.props.auth.user" class="container mx-auto mt-6 p-4">
        <h2 class="text-lg font-semibold text-base-content mb-3">{{ t('mySavedTimes') }}</h2>
        <div v-if="userSavedTimes.length > 0" class="overflow-x-auto">
            <table class="table w-full">
                <thead>
                    <tr>
                        <th>{{ t('route') }}</th>
                        <th>{{ t('stop') }}</th>
                        <th>{{ t('schedule') }}</th>
                        <th>{{ t('savedTimes') }}</th>
                        <th>{{ t('actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="saved in sortedSavedTimes" :key="saved.id"
                        class="hover:bg-base-200 transition-colors duration-200 cursor-pointer"
                        @click="viewSavedRoute(saved, saved.time)">
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: '#' + saved.route_color }"></div>
                                {{ saved.route_name }}
                            </div>
                        </td>
                        <td>{{ saved.stop_name }}</td>
                        <td>
                            <span class="badge badge-sm"
                                  :class="saved.schedule_type === 'workday' ? 'badge-primary' : 'badge-secondary'">
                                {{ t(saved.schedule_type) }}
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-primary">{{ saved.time }}</span>
                        </td>
                        <td>
                            <button @click.stop="deleteSavedTime(saved.id)" class="btn btn-error btn-xs">
                                {{ t('delete') }}
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div v-else class="text-base-content/70">
            <p>{{ t('noSavedTimesYet') }}</p>
        </div>
    </div>
</template>
