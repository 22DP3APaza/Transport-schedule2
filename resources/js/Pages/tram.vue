<script setup>
import { Head, Link, usePage, router } from '@inertiajs/vue3'; // Import `router`
import { ref, computed } from 'vue';

const page = usePage();
const routes = ref(page.props.routes || []);
const from = ref('');
const to = ref('');

// Sort the routes
const sortedRoutes = computed(() =>
    [...routes.value].sort((a, b) => Number(a.route_short_name) - Number(b.route_short_name))
);

const isActive = (routeName) => {
    return page.url.startsWith(routeName);
};

// Search for the matching route and navigate
const searchRoute = () => {
    if (from.value && to.value) {
        // Use `router.post` to send the "From" and "To" values to the backend
        router.post('/search-route', {
            from: from.value,
            to: to.value,
            type: 'tram' // Add a type parameter to filter by tram routes
        });
    } else {
        alert("Please enter both 'From' and 'To' values.");
    }
};

const routeDetailsUrl = (routeId) => route('route.details', { route_id: routeId });
</script>

<template>
    <Head title="Tram" />
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
            <a href="/bus" :class="['btn btn-ghost text-xl', isActive('/bus') ? 'bg-blue-500 text-white' : '']">Bus</a>
        </div>
        <div class="navbar bg-base-100">
            <a href="/trolleybus" :class="['btn btn-ghost text-xl', isActive('/trolleybus') ? 'bg-blue-500 text-white' : '']">Trolleybus</a>
        </div>
        <div class="navbar bg-base-100">
            <a href="/tram" :class="['btn btn-ghost text-xl', isActive('/tram') ? 'bg-blue-500 text-white' : '']">Tram</a>
        </div>
        <div class="navbar bg-base-100">
            <a href="/train" :class="['btn btn-ghost text-xl', isActive('/train') ? 'bg-blue-500 text-white' : '']">Train</a>
        </div>
    </div>
        <div class="navbar-end">
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
                    <li><a>Settings</a></li>
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
