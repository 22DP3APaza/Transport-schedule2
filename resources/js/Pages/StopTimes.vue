<script setup>
import { ref, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const { props } = usePage();
const trips = ref(props.trips || {});
const initialTripId = ref(props.initialTripId || '');
const selectedStopId = ref(props.selectedStopId || '');
const selectedDepartureTime = ref(props.selectedDepartureTime || '');
const routeId = ref(props.routeId || '');
const error = ref(props.error || '');

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
</script>

<template>
    <Head title="Stop Times" />
    <div class="container mx-auto mt-6 p-4">
        <button @click="goBack" class="btn btn-outline mb-4">← Back</button>

        <div v-if="error" class="alert alert-error mb-4">
            {{ error }}
        </div>

        <div class="space-y-6">
            <div v-for="[tripId, stops] in matchingTrips" :key="tripId" class="bg-base-100 p-4 rounded-lg border">
                <h2 class="text-lg font-semibold mb-2"></h2>

                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead>
                            <tr>
                                <th>Stop Name</th>
                                <th>Departure Time</th>
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
            <p>No matching trips found.</p>
        </div>
    </div>
</template>
