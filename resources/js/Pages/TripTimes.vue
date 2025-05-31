<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    trip: Object,
    stops: Array,
    selectedStopId: String,
    selectedDepartureTime: String
});

const { t } = useI18n();

const formattedStops = computed(() => {
    return props.stops.map(stop => ({
        ...stop,
        isHighlighted: stop.stop_id === props.selectedStopId &&
                      stop.departure_time === props.selectedDepartureTime
    }));
});

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
    <Head :title="t('tripSchedule')" />

    <div class="container mx-auto p-4">
        <div class="flex justify-between items-center mb-6">
            <Link :href="'/train/details/' + trip.route_id" class="btn btn-ghost">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                {{ t('back') }}
            </Link>
        </div>

        <div class="bg-base-100 p-6 rounded-lg shadow-xl">
            <h1 class="text-2xl font-bold mb-4">{{ t('tripSchedule') }}</h1>

            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead>
                        <tr>
                            <th>{{ t('stopName') }}</th>
                            <th>{{ t('departureTime') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="stop in formattedStops"
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
</template>
