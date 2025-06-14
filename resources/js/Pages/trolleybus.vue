<script setup>
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';

const { t, locale } = useI18n();
const page = usePage();
const routes = ref(page.props.routes || []);
const from = ref('');
const to = ref('');
const stops = ref([]);
const filteredFromStops = ref([]);
const filteredToStops = ref([]);
const showFromDropdown = ref(false);
const showToDropdown = ref(false);
const isLoadingStops = ref(false);

// Extract stop names from route names (format: "Stop1 - Stop2")
const extractStopsFromRoutes = (routes) => {
  const routeStops = [];
  routes.forEach(route => {
    if (route.route_long_name.includes(' - ')) {
      const [stop1, stop2] = route.route_long_name.split(' - ');
      routeStops.push({
        stop_name: stop1.trim(),
        from_route: true
      });
      routeStops.push({
        stop_name: stop2.trim(),
        from_route: true
      });
    }
  });
  return routeStops;
};

// Combine stops from API and routes, ensuring uniqueness
const combineStops = (apiStops, routeStops) => {
  const allStops = [...apiStops];
  routeStops.forEach(routeStop => {
    if (!allStops.some(s => s.stop_name.toLowerCase() === routeStop.stop_name.toLowerCase())) {
      allStops.push(routeStop);
    }
  });
  return allStops;
};

// Fetch stops data and combine with stops extracted from routes
const fetchStops = async () => {
  try {
    isLoadingStops.value = true;
    const [stopsResponse] = await Promise.all([
      axios.get('/api/stops', {
        params: { type: 'trolleybus' }
      }),
    ]);

    // Get unique stops from API
    const apiStops = stopsResponse.data.reduce((acc, stop) => {
      if (!acc.some(s => s.stop_name.toLowerCase() === stop.stop_name.toLowerCase())) {
        acc.push(stop);
      }
      return acc;
    }, []);

    // Extract stops from route names
    const routeStops = extractStopsFromRoutes(routes.value);

    // Extract stops from trip headsigns
    const tripHeadsignStops = [];
    routes.value.forEach(route => {
      if (route.trip_headsign) {
        const parts = route.trip_headsign.split(' - ').map(part => part.trim());
        parts.forEach(part => {
          if (!tripHeadsignStops.some(s => s.stop_name.toLowerCase() === part.toLowerCase())) {
            tripHeadsignStops.push({
              stop_name: part,
              from_route: true
            });
          }
        });
      }
    });

    // Combine all stops and deduplicate
    stops.value = combineStops(apiStops, [...routeStops, ...tripHeadsignStops]);
  } catch (error) {
    console.error('Error fetching stops:', error);
  } finally {
    isLoadingStops.value = false;
  }
};

// Sort the routes
const sortedRoutes = computed(() =>
  [...routes.value].sort((a, b) => Number(a.route_short_name) - Number(b.route_short_name))
);

// Watch for changes in from input
watch(from, (newVal) => {
  if (newVal.length > 1) {
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
      const response = await axios.get('/api/possible-destinations', {
        params: {
          from: from.value,
          type: 'trolleybus'
        }
      });
      filteredToStops.value = response.data.filter(stop =>
      stop.stop_name.toLowerCase().includes(newVal.toLowerCase())
    ).slice(0, 5);
    showToDropdown.value = filteredToStops.value.length > 0;
    } catch (error) {
      console.error('Error fetching possible destinations:', error);
      filteredToStops.value = [];
    }
  } else {
    showToDropdown.value = false;
  }
});

// Select a stop from the from dropdown
const selectFromStop = (stop) => {
  from.value = stop.stop_name;
  showFromDropdown.value = false;
  // Clear the 'to' field when selecting a new 'from' stop
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

  // Add any missing stops to our local stops array
  const fromStop = filteredToStops.value.find(s => s.stop_name === from.value);
  const toStop = filteredFromStops.value.find(s => s.stop_name === to.value);

  if (fromStop && !stops.value.some(s => s.stop_name.toLowerCase() === fromStop.stop_name.toLowerCase())) {
    stops.value.push(fromStop);
  }
  if (toStop && !stops.value.some(s => s.stop_name.toLowerCase() === toStop.stop_name.toLowerCase())) {
    stops.value.push(toStop);
  }

  // Also switch the filtered stops if needed
  const tempFiltered = [...filteredFromStops.value];
  filteredFromStops.value = [...filteredToStops.value];
  filteredToStops.value = tempFiltered;
};

// Search for the matching route and navigate
const searchRoute = () => {
  if (from.value && to.value) {
    router.post('/trolleybus/search', {
      from: from.value,
      to: to.value
    });
  } else {
    alert(t('pleaseEnterValues'));
  }
};

const isActive = (routeName) => {
  return page.url.startsWith(routeName);
};

const routeDetailsUrl = (routeId) => route('route.details', { route_id: routeId });

// Get color based on transport type
const getTransportColor = (transportType) => {
  switch(transportType) {
    case 'bus': return '#DCA223';
    case 'trolleybus': return '#008DCA';
    case 'tram': return '#E6000B';
    default: return '#3490dc';
  }
};

// Theme Management
const currentTheme = ref('light');

// Load the theme from localStorage on initialization
onMounted(() => {
  const savedTheme = localStorage.getItem('theme');
  if (savedTheme) {
    currentTheme.value = savedTheme;
    document.querySelector('html').setAttribute('data-theme', savedTheme);
  }

  // Load language preference
  const savedLanguage = localStorage.getItem('language');
  if (savedLanguage) {
    locale.value = savedLanguage;
  }

  // Fetch stops data when component mounts
  fetchStops();
});

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
</script>

<template>
  <Head :title="t('trolleybus')" />
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
          <li v-if="!$page.props.auth.user">
            <a :href="route('login')">{{ t('login') }}</a>
          </li>
          <li v-if="$page.props.auth.user">
            <Link href="/settings">{{ t('settings') }}</Link>
          </li>
          <li v-if="$page.props.auth.user?.admin">
            <Link href="/admin/users">{{ t('adminPanel') }}</Link>
          </li>
          <li v-if="$page.props.auth.user">
            <Link :href="route('logout')" method="post">{{ t('logout') }}</Link>
          </li>
        </ul>
      </div>
    </div>
  </header>

  <div class="middle" style="display: flex; flex-direction: column; align-items: center; padding-top: 20px; gap: 20px;">
    <h1 style="font-size: 2em; font-weight: bold;">{{ t('publicTransport') }}</h1>

    <div class="flex items-center gap-4">
      <div class="flex flex-col gap-4">
        <!-- From Input -->
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

        <!-- To Input -->
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

      <!-- Switch Button (rotated 90 degrees) -->
      <button @click="switchStops" class="btn btn-ghost p-2 self-center" :title="t('switchStops')">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 transform rotate-90">
          <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
        </svg>
      </button>
    </div>

    <button @click="searchRoute" class="btn btn-primary mt-4">{{ t('search') }}</button>

    <div class="container w-full max-w-xl mt-6 flex flex-wrap gap-2 justify-center">
      <template v-if="sortedRoutes.length">
        <button
          v-for="route in sortedRoutes"
          :key="route.route_id"
          @click="() => router.visit(routeDetailsUrl(route.route_id))"
          :title="route.route_long_name"
          class="btn btn-square w-10 h-10 flex items-center justify-center text-white hover:brightness-90 transition rounded-md shadow text-sm font-bold"
          :style="{ backgroundColor: getTransportColor('trolleybus') }">
          {{ route.route_short_name }}
        </button>
      </template>
      <p v-else class="text-gray-500">{{ t('noRoutes') }}</p>
    </div>
  </div>
</template>
