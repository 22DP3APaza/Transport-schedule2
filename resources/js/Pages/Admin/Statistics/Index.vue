<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    statistics: Object,
});
</script>

<template>
    <Head :title="$t('adminPanel.statistics')" />

    <AdminLayout>
        <div class="p-8">
            <h1 class="text-3xl font-bold mb-8">{{ $t('adminPanel.dashboard.title') }}</h1>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- Total Users -->
                <div class="stats shadow">
                    <div class="stat">
                        <div class="stat-title">{{ $t('adminPanel.dashboard.totalUsers') }}</div>
                        <div class="stat-value">{{ statistics.total_users }}</div>
                    </div>
                </div>

                <!-- Total Routes -->
                <div class="stats shadow">
                    <div class="stat">
                        <div class="stat-title">{{ $t('adminPanel.dashboard.totalRoutes') }}</div>
                        <div class="stat-value">{{ statistics.total_routes }}</div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Recent Users -->
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title">{{ $t('adminPanel.dashboard.recentUsers') }}</h2>
                        <div class="overflow-x-auto">
                            <table class="table table-zebra">
                                <thead>
                                    <tr>
                                        <th>{{ $t('adminPanel.dashboard.table.username') }}</th>
                                        <th>{{ $t('adminPanel.dashboard.table.email') }}</th>
                                        <th>{{ $t('adminPanel.dashboard.table.joined') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="user in statistics.recent_users" :key="user.id">
                                        <td>{{ user.username }}</td>
                                        <td>{{ user.email }}</td>
                                        <td>{{ new Date(user.created_at).toLocaleDateString() }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Recent Routes -->
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title">{{ $t('adminPanel.dashboard.recentRoutes') }}</h2>
                        <div class="overflow-x-auto">
                            <table class="table table-zebra">
                                <thead>
                                    <tr>
                                        <th>{{ $t('adminPanel.dashboard.table.routeNumber') }}</th>
                                        <th>{{ $t('adminPanel.dashboard.table.name') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="route in statistics.recent_routes" :key="route.route_id">
                                        <td>{{ route.route_short_name }}</td>
                                        <td>{{ route.route_long_name }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
