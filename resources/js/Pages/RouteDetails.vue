<script setup>
import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const route = ref(page.props.route);
const stops = ref(page.props.stops || []);

const formatTime = (time) => {
    if (!time) return 'N/A';
    return time.slice(0, 5); // Show HH:MM only
};

const goBack = () => window.history.back();
</script>

<template>
    <Head :title="route.route_short_name" />
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

</header>


    <!-- Route Details Section -->
    <div class="container mx-auto mt-6 p-4">
        <button @click="goBack" class="btn btn-outline mb-4">← Back</button>

        <h1 class="text-3xl font-bold mb-4">
             {{ route.route_long_name }}
        </h1>

        <div class="overflow-x-auto">
            <table class="table-auto w-full border-collapse border border-gray-300" v-if="stops.length > 0">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border p-2 text-left">Stop Name</th>
                        <th class="border p-2 text-left">Arrival Time</th>
                        <th class="border p-2 text-left">Departure Time</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="stop in stops" :key="stop.id" class="hover:bg-gray-50">
                        <td class="border p-2">{{ stop.stop_name }}</td>
                        <td class="border p-2">{{ formatTime(stop.arrival_time) }}</td>
                        <td class="border p-2">{{ formatTime(stop.departure_time) }}</td>
                    </tr>
                </tbody>
            </table>
            <p v-else>No stops available for this route.</p>
        </div>
    </div>
</template>
