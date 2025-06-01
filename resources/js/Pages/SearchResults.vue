<script setup>
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const page = usePage();
const routes = ref(page.props.routes || []);
const from = ref(page.props.from || '');
const to = ref(page.props.to || '');
const type = ref(page.props.type || 'bus');

// Get color based on transport type
const getTransportColor = (transportType) => {
    switch(transportType) {
        case 'bus': return '#DCA223';
        case 'trolleybus': return '#008DCA';
        case 'tram': return '#E6000B';
        default: return '#3490dc';
    }
};

const routeDetailsUrl = (route) => {
    // For train routes, we need both route_id and trip_id
    if (type.value === 'train' && route.trip_id) {
        return `/train/details/${route.route_id}/${route.trip_id}`;
    }
    // For other transport types, use the regular route.details endpoint
    return route('route.details', { route_id: route.route_id });
};

// Get transport type for a specific route
const getRouteTransportType = (route) => {
    if (type.value === 'all') {
        return route.transport_type || 'bus'; // Use the tagged type or default to bus
    }
    return type.value;
};

const goBack = () => {
    // Determine the correct back URL based on the type
    let backUrl = '/';
    switch(type.value) {
        case 'bus':
        case 'trolleybus':
        case 'tram':
            backUrl = `/${type.value}`;
            break;
        case 'all':
            backUrl = '/'; // Go to home page if type is 'all'
            break;
    }
    router.visit(backUrl);
};

// Handle page refresh by preserving search parameters
onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    if (!urlParams.has('from') && from.value && to.value) {
        // If URL doesn't have parameters but we have them in props, add them to URL
        const newUrl = new URL(window.location.href);
        newUrl.searchParams.set('from', from.value);
        newUrl.searchParams.set('to', to.value);
        newUrl.searchParams.set('type', type.value);
        window.history.replaceState({}, '', newUrl);
    }
});
</script>

<template>
    <Head :title="t('searchResults')" />

    <div class="container mx-auto p-4">
        <div class="flex justify-between items-center mb-6">
            <button @click="goBack" class="btn btn-ghost">
                ← {{ t('back') }}
            </button>
        </div>

        <div class="bg-base-200 rounded-lg p-6">
            <h1 class="text-2xl font-bold mb-4">{{ t('searchResults') }}</h1>
            <div class="text-lg mb-6">
                {{ t('routesFrom') }} <span class="font-semibold">{{ from }}</span>
                {{ t('to') }} <span class="font-semibold">{{ to }}</span>
            </div>

            <div v-if="routes.length" class="grid gap-4">
                <div v-for="route in routes" :key="route.route_id"
                    class="bg-base-100 p-4 rounded-lg shadow-md hover:shadow-lg transition-shadow cursor-pointer"
                    @click="router.visit(routeDetailsUrl(route))">
                    <div class="flex items-center gap-4">
                        <div v-if="type !== 'train'" class="btn btn-square w-auto h-10 px-4 flex items-center justify-center text-white hover:brightness-90 transition rounded-md shadow text-sm font-bold"
                            :style="{ backgroundColor: getTransportColor(getRouteTransportType(route)) }">
                            {{ route.route_short_name }}
                        </div>
                        <div class="flex-grow">
                            <div class="font-semibold">{{ route.route_long_name }}</div>
                            <div v-if="type === 'all'" class="text-sm text-base-content/70">
                                {{ t(getRouteTransportType(route)) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="text-center py-8 text-base-content/70">
                {{ t('noRoutesFound') }}
            </div>
        </div>
    </div>
</template>
