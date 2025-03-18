<script setup>
import { ref, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

// Get the page props
const page = usePage();
const stopTimes = ref([]); // Stores the stop times for the trip

// Fetch stop times passed from the backend
onMounted(() => {
    stopTimes.value = page.props.stopTimes || [];
});
</script>

<template>
    <Head title="Stop Times" />

    <!-- Header Section -->
    <header class="navbar bg-base-100">
        <div class="navbar-start">
            <button class="btn btn-square btn-ghost" @click="$router.back()">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="h-6 w-6 stroke-current">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </button>
        </div>
        <div class="navbar-center">
            <h1 class="text-xl font-bold">Stop Times</h1>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container mx-auto mt-6 p-4">
        <div v-if="stopTimes.length > 0" class="overflow-x-auto rounded-box border border-base-content/5 bg-base-100 mt-2">
            <table class="table w-full">
                <thead>
                    <tr>
                        <th>Sequence</th>
                        <th>Stop Name</th>
                        <th>Arrival Time</th>
                        <th>Departure Time</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="stop in stopTimes" :key="stop.sequence">
                        <td>{{ stop.sequence }}</td>
                        <td>{{ stop.stop_name }}</td>
                        <td>{{ stop.arrival_time }}</td>
                        <td>{{ stop.departure_time }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div v-else>
            <p class="text-center text-gray-500">No stop times available for this trip.</p>
        </div>
    </div>
</template>
