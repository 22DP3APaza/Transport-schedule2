<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';

const { locale } = useI18n();
const isLoading = ref(false);

const switchLanguage = (lang) => {
  locale.value = lang
  localStorage.setItem('locale', lang)
}

// Add loading state handlers
onMounted(() => {
    router.on('start', () => {
        isLoading.value = true;
    });

    router.on('finish', () => {
        isLoading.value = false;
    });
});
</script>

<template>
    <div class="min-h-screen bg-base-200">
        <Head :title="$page.props.title" />

        <!-- Loading Progress Bar -->
        <div v-if="isLoading" class="fixed top-0 left-0 right-0 z-50">
            <div class="h-1 bg-base-100">
                <div class="h-1 bg-primary loading-bar"></div>
            </div>
        </div>

        <!-- Sidebar and Content -->
        <div class="flex">
            <!-- Sidebar -->
            <div class="w-64 min-h-screen bg-base-100 shadow-lg hidden md:block">
                <ul class="menu p-4">
                    <li class="menu-title">{{ $t('adminPanel.title') }}</li>

                    <!-- Home -->
                    <li>
                        <Link
                            href="/"
                            :class="{
                                'active': $page.url === '/',
                                'loading': isLoading && $page.url === '/'
                            }"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            {{ $t('adminPanel.home') }}
                            <span v-if="isLoading && $page.url === '/'" class="loading loading-spinner loading-xs ml-2"></span>
                        </Link>
                    </li>

                    <!-- Statistics -->
                    <li>
                        <Link
                            href="/admin/statistics"
                            :class="{
                                'active': $page.url.startsWith('/admin/statistics'),
                                'loading': isLoading && $page.url.startsWith('/admin/statistics')
                            }"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            {{ $t('adminPanel.statistics') }}
                            <span v-if="isLoading && $page.url.startsWith('/admin/statistics')" class="loading loading-spinner loading-xs ml-2"></span>
                        </Link>
                    </li>

                    <!-- Users -->
                    <li>
                        <Link
                            href="/admin/users"
                            :class="{
                                'active': $page.url.startsWith('/admin/users'),
                                'loading': isLoading && $page.url.startsWith('/admin/users')
                            }"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            {{ $t('adminPanel.users') }}
                            <span v-if="isLoading && $page.url.startsWith('/admin/users')" class="loading loading-spinner loading-xs ml-2"></span>
                        </Link>
                    </li>

                    <!-- News -->
                    <li>
                        <Link
                            href="/admin/news"
                            :class="{
                                'active': $page.url.startsWith('/admin/news'),
                                'loading': isLoading && $page.url.startsWith('/admin/news')
                            }"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5a2.5 2.5 0 00-2.5-2.5H15" />
                            </svg>
                            {{ $t('news') }}
                            <span v-if="isLoading && $page.url.startsWith('/admin/news')" class="loading loading-spinner loading-xs ml-2"></span>
                        </Link>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <main class="flex-1">
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
.loading-bar {
    animation: loading 2s ease-in-out infinite;
}

@keyframes loading {
    0% {
        width: 0%;
    }
    50% {
        width: 100%;
    }
    100% {
        width: 0%;
    }
}

/* Add a subtle transition for the loading spinner */
.loading-spinner {
    transition: opacity 0.2s ease-in-out;
}
</style>
