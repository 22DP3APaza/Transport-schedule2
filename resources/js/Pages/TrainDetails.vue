<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';

const props = defineProps({
    route: Object,
    trips: Array, // This prop will still be passed, but not used for selection
    selectedTrip: Object, // This prop will be the primary source for the trip
    stops: Array
});

const { t } = useI18n();
const selectedStop = ref(null);
const stopTimes = ref([]);
const isLoading = ref(false);
const selectedDate = ref(formatDate(new Date()));
const dateOptions = ref([]);
const error = ref(null);
const noServiceMessage = ref(null);
// Removed: const selectedTripId = ref(props.selectedTrip?.trip_id);
const showTripDetails = ref(false);
const selectedTripStops = ref([]);
const selectedTime = ref(null);

// Generate next 7 days for date selection
onMounted(() => {
    const dates = [];
    for (let i = 0; i < 7; i++) {
        const date = new Date();
        date.setDate(date.getDate() + i);
        dates.push({
            value: formatDate(date),
            label: formatDateLabel(date)
        });
    }
    dateOptions.value = dates;

    // Automatically select the first stop and load its times on mount
    if (props.stops && props.stops.length > 0) {
        selectedStop.value = props.stops[0];
        loadStopTimes(selectedStop.value);
    }
});

// Removed: watch for selectedTripId as trip selection is removed
// watch(selectedTripId, (newTripId, oldTripId) => {
//     if (newTripId && selectedStop.value) {
//         loadStopTimes(selectedStop.value);
//     }
// });

// Removed: tripOptions computed property as trip selection is removed
// const tripOptions = computed(() => {
//     const optionsMap = new Map();
//     const addedTripIds = new Set();

//     props.trips.forEach(trip => {
//         const parts = trip.full_name.split(' - ');
//         if (parts.length === 2) {
//             const stationA = parts[0].trim();
//             const stationB = parts[1].trim();
//             const canonicalRouteKey = [stationA, stationB].sort().join('-');

//             if (!optionsMap.has(canonicalRouteKey)) {
//                 optionsMap.set(canonicalRouteKey, []);
//             }
//             optionsMap.get(canonicalRouteKey).push(trip);
//         } else {
//             if (!addedTripIds.has(trip.trip_id)) {
//                 optionsMap.set(trip.trip_id, [trip]);
//             }
//         }
//     });

//     const finalOptions = [];
//     optionsMap.forEach(tripsInRoute => {
//         if (tripsInRoute.length === 1) {
//             if (!addedTripIds.has(tripsInRoute[0].trip_id)) {
//                 finalOptions.push({
//                     value: tripsInRoute[0].trip_id,
//                     label: tripsInRoute[0].full_name
//                 });
//                 addedTripIds.add(tripsInRoute[0].trip_id);
//             }
//         } else if (tripsInRoute.length > 1) {
//             const fromToTrips = tripsInRoute.filter(t => {
//                 const parts = t.full_name.split(' - ');
//                 return parts.length === 2 && parts[0].trim() === tripsInRoute[0].full_name.split(' - ')[0].trim();
//             });
//             const toFromTrips = tripsInRoute.filter(t => {
//                 const parts = t.full_name.split(' - ');
//                 return parts.length === 2 && parts[0].trim() === tripsInRoute[0].full_name.split(' - ')[1].trim();
//             });

//             if (fromToTrips.length > 0 && !addedTripIds.has(fromToTrips[0].trip_id)) {
//                 finalOptions.push({
//                     value: fromToTrips[0].trip_id,
//                     label: fromToTrips[0].full_name
//                 });
//                 addedTripIds.add(fromToTrips[0].trip_id);
//             }

//             if (toFromTrips.length > 0 && !addedTripIds.has(toFromTrips[0].trip_id)) {
//                 finalOptions.push({
//                     value: toFromTrips[0].trip_id,
//                     label: `${toFromTrips[0].full_name} (${t('oppositeDirection')})`
//                 });
//                 addedTripIds.add(toFromTrips[0].trip_id);
//             }

//             tripsInRoute.forEach(trip => {
//                 if (!addedTripIds.has(trip.trip_id)) {
//                     finalOptions.push({
//                         value: trip.trip_id,
//                         label: trip.full_name
//                     });
//                     addedTripIds.add(trip.trip_id);
//                 }
//             });
//         }
//     });
//     return finalOptions.sort((a, b) => a.label.localeCompare(b.label));
// });


function formatDate(date) {
    return date.toISOString().split('T')[0].replace(/-/g, '');
}

function formatDateLabel(date) {
    const options = { weekday: 'long', month: 'long', day: 'numeric' };
    return date.toLocaleDateString(undefined, options);
}

const loadStopTimes = async (stop) => {
    if (!stop) return;

    isLoading.value = true;
    selectedStop.value = stop;
    stopTimes.value = [];
    error.value = null;
    noServiceMessage.value = null;

    console.log('Loading stop times for:', {
        stop_id: stop.stop_id,
        route_id: props.route.route_id,
        // Use props.selectedTrip?.trip_id directly as there's no selection
        trip_id: props.selectedTrip?.trip_id,
        date: selectedDate.value
    });

    try {
        const response = await axios.get(`/api/train/stop-times/${stop.stop_id}`, {
            params: {
                route_id: props.route.route_id,
                // Use props.selectedTrip?.trip_id directly
                trip_id: props.selectedTrip?.trip_id,
                date: selectedDate.value
            }
        });

        console.log('Full API Response:', response.data);

        if (response.data.error) {
            if (response.data.error === 'No service on this date') {
                noServiceMessage.value = {
                    main: t('noServiceOnDate'),
                    sub: t('trySelectingDifferentDate')
                };
            } else {
                error.value = response.data.error;
            }
            console.error('API Error:', response.data.error, response.data.debug);
        } else {
            stopTimes.value = response.data.data || [];
            console.log('Stop times loaded:', stopTimes.value);
            if (stopTimes.value.length === 0) {
                noServiceMessage.value = {
                    main: t('noTimesAvailable'),
                    sub: t('checkDifferentTrip') // This message might need adjustment if there's no "different trip" to check
                };
            }
        }
    } catch (error) {
        console.error('Error loading stop times:', error);
        error.value = t('failedToLoadTimes');
    } finally {
        isLoading.value = false;
    }
};

// Removed: onTripChange as trip selection is removed
// const onTripChange = (tripId) => {
//     router.visit(`/train/details/${props.route.route_id}/${tripId}`);
// };

const onDateChange = () => {
    if (selectedStop.value) {
        loadStopTimes(selectedStop.value);
    }
};

const handleTimeClick = (time) => {
    if (!time.trip_id || !time.departure_time || !selectedStop.value) {
        console.error('Missing required data for time click');
        return;
    }

    router.visit(`/train/trip/${time.trip_id}`, {
        data: {
            stop_id: selectedStop.value.stop_id,
            departure_time: time.departure_time
        }
    });
};

const closeTripDetails = () => {
    showTripDetails.value = false;
    selectedTime.value = null;
    selectedTripStops.value = [];
};

const formatTime = (time) => {
    if (!time) return '';

    // Extract hours and minutes
    const [hours, minutes] = time.split(':');
    if (!hours || !minutes) return time;

    const numHours = parseInt(hours);

    // Handle times after midnight (24:xx:xx or greater)
    if (numHours >= 24) {
        const adjustedHours = numHours - 24;
        return `${adjustedHours.toString().padStart(2, '0')}:${minutes}`;
    }

    return `${hours}:${minutes}`;
};
</script>

<template>
    <Head :title="t('trainSchedule')" />

    <div class="container mx-auto p-4">
        <div class="flex justify-between items-center mb-6">
            <Link href="/train" class="btn btn-ghost">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                {{ t('back') }}
            </Link>
        </div>

        <div class="flex gap-4 mb-6">
            <!-- Removed Trip Selection:
            <select
                v-model="selectedTripId"
                @change="onTripChange($event.target.value)"
                class="select select-bordered flex-1"
            >
                <option v-for="trip in tripOptions" :key="trip.value" :value="trip.value">
                    {{ trip.label }}
                </option>
            </select>
            -->

            <select
                v-model="selectedDate"
                @change="onDateChange"
                class="select select-bordered flex-1"
            >
                <option v-for="date in dateOptions" :key="date.value" :value="date.value">
                    {{ date.label }}
                </option>
            </select>
        </div>

        <div class="grid md:grid-cols-2 gap-6">
            <div class="bg-base-100 p-4 rounded-lg shadow">
                <h2 class="text-xl font-bold mb-4">{{ t('stops') }}</h2>
                <div class="space-y-2">
                    <div v-for="stop in props.stops"
                        :key="stop.stop_id"
                        @click="loadStopTimes(stop)"
                        :class="[
                            'p-3 rounded cursor-pointer transition-colors',
                            selectedStop?.stop_id === stop.stop_id
                                ? 'bg-primary text-primary-content'
                                : 'hover:bg-base-200'
                        ]"
                    >
                        {{ stop.stop_name }}
                        </div>
                </div>
            </div>

            <div class="bg-base-100 p-4 rounded-lg shadow">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold">
                        {{ selectedStop ? selectedStop.stop_name : t('selectStop') }}
                        </h2>
                </div>

                <div v-if="isLoading" class="flex justify-center items-center h-40">
                    <span class="loading loading-spinner loading-lg"></span>
                </div>

                <div v-else-if="selectedStop && stopTimes.length" class="overflow-x-auto">
                    <table class="table w-full">
                        <thead>
                            <tr>
                                <th>{{ t('departureTime') }}</th>
                                <th>{{ t('route') }}</th>
                                <th>{{ t('direction') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="time in stopTimes" :key="time.trip_id"
                                class="cursor-pointer hover:bg-base-200"
                                @click="handleTimeClick(time)">
                                <td class="font-medium">{{ formatTime(time.departure_time) }}</td>
                                <td class="text-sm">
                                    {{ time.from_station }} → {{ time.to_station }}
                                </td>
                                <td class="text-sm">
                                    {{ t('to') }} {{ time.headsign }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="showTripDetails" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div class="bg-base-100 p-6 rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xl font-bold">{{ t('tripSchedule') }}</h3>
                            <button @click="closeTripDetails" class="btn btn-ghost btn-sm">×</button>
                        </div>

                        <div v-if="isLoading" class="flex justify-center items-center py-8">
                            <span class="loading loading-spinner loading-lg"></span>
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="table w-full">
                                <thead>
                                    <tr>
                                        <th>{{ t('stopName') }}</th>
                                        <th>{{ t('departureTime') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="stop in selectedTripStops"
                                        :key="stop.stop_id"
                                        :class="{
                                            'bg-primary text-primary-content': stop.isHighlighted
                                        }">
                                        <td>{{ stop.stop_name }}</td>
                                        <td>{{ formatTime(stop.departure_time) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div v-else-if="selectedStop && noServiceMessage" class="text-center py-8">
                    <div class="text-gray-500">{{ noServiceMessage.main }}</div>
                    <div class="text-sm mt-2 text-gray-400">{{ noServiceMessage.sub }}</div>
                </div>

                <div v-else-if="selectedStop" class="text-center py-8 text-gray-500">
                    {{ t('noTimesAvailable') }}
                </div>

                <div v-else class="text-center py-8 text-gray-500">
                    {{ t('pleaseSelectStop') }}
                </div>
            </div>
        </div>

        <div v-if="error" class="mt-4 p-4 bg-error text-error-content rounded-lg">
            {{ error }}
        </div>
    </div>
</template>
