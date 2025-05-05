<script setup>
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';

const page = usePage();
const routes = ref(page.props.routes || []);
const from = ref('');
const to = ref('');

// Sort the routes
const sortedRoutes = computed(() =>
    [...routes.value].sort((a, b) => Number(a.route_short_name) - Number(b.route_short_name))
);

// Search for the matching route and navigate
const searchRoute = () => {
    if (from.value && to.value) {
        router.post('/search-route', {
            from: from.value,
            to: to.value,
            type: 'tram'
        });
    } else {
        alert("Please enter both 'From' and 'To' values.");
    }
};

const isActive = (routeName) => {
    return page.url.startsWith(routeName);
};

const routeDetailsUrl = (routeId) => route('route.details', { route_id: routeId });

const getTransportColor = (transportType) => {
    switch(transportType) {
        case 'bus': return '#DCA223';
        case 'trolleybus': return '#008DCA';
        case 'tram': return '#E6000B';
        default: return '#3490dc';
    }
};

// Theme Management
const currentTheme = ref('light'); // Default theme is light

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
    localStorage.setItem('theme', currentTheme.value); // Save the theme to local storage
};

// Language Management
const currentLanguage = ref('en'); // Default language is English
const changeLanguage = (language) => {
    currentLanguage.value = language;
    console.log(`Language changed to: ${language}`);
    // Here you can trigger an API call or update the app's language dynamically
};
</script>

<template>
    <Head title="Tram" />
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
                <a href="/bus" :class="['btn btn-ghost text-xl', isActive('/bus') ? 'text-white' : '']" :style="isActive('/bus') ? { backgroundColor: getTransportColor('bus') } : {}">Bus</a>
            </div>
            <div class="navbar bg-base-100">
                <a href="/trolleybus" :class="['btn btn-ghost text-xl', isActive('/trolleybus') ? 'text-white' : '']" :style="isActive('/trolleybus') ? { backgroundColor: getTransportColor('trolleybus') } : {}">Trolleybus</a>
            </div>
            <div class="navbar bg-base-100">
                <a href="/tram" :class="['btn btn-ghost text-xl', isActive('/tram') ? 'text-white' : '']" :style="isActive('/tram') ? { backgroundColor: getTransportColor('tram') } : {}">Tram</a>
            </div>
            <div class="navbar bg-base-100">
                <a href="/train" :class="['btn btn-ghost text-xl', isActive('/train') ? 'bg-blue-500 text-white' : '']">Train</a>
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
                        <a :class="{ 'bg-primary text-white': currentLanguage === 'en' }">English</a>
                    </li>
                    <li @click="changeLanguage('lv')">
                        <a :class="{ 'bg-primary text-white': currentLanguage === 'lv' }">Latvian</a>
                    </li>
                </ul>
            </div>
            <!-- Theme Toggle Button -->
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
                        <a :href="route('login')">Login</a>
                    </li>
                    <li>
                        <Link href="/settings">Settings</Link>
                    </li>
                    <li v-if="$page.props.auth.user">
                        <Link :href="route('logout')" method="post">Log Out</Link>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <div class="middle" style="display: flex; flex-direction: column; align-items: center; padding-top: 20px; gap: 20px;">
        <h1 style="font-size: 2em; font-weight: bold;">Publisko transportu saraksti</h1>

        <!-- From and To Input Fields -->
        <input type="text" v-model="from" placeholder="From" class="input input-ghost w-full max-w-xs" style="border-bottom: 2px solid black;" />
        <input type="text" v-model="to" placeholder="To" class="input input-ghost w-full max-w-xs" style="border-bottom: 2px solid black;" />

        <!-- Search Button -->
        <button @click="searchRoute" class="btn btn-primary mt-4">Search</button>

        <!-- Sorted Routes Buttons -->
        <div class="container w-full max-w-xl mt-6 flex flex-wrap gap-2 justify-center">
            <template v-if="sortedRoutes.length">
                <button
                    v-for="route in sortedRoutes"
                    :key="route.route_id"
                    @click="() => router.visit(routeDetailsUrl(route.route_id))"
                    :title="route.route_long_name"
                    class="btn btn-square w-10 h-10 flex items-center justify-center text-white hover:brightness-90 transition rounded-md shadow text-sm font-bold"
                    :style="{ backgroundColor: route.route_color ? `#${route.route_color}` : '#3490dc' }">
                    {{ route.route_short_name }}
                </button>
            </template>
            <p v-else class="text-gray-500">No routes available.</p>
        </div>
    </div>
</template>
