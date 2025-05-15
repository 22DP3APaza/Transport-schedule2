<script setup>
import { Head, Link } from '@inertiajs/vue3';

import { useI18n } from 'vue-i18n'

const { locale } = useI18n()

const switchLanguage = (lang) => {
  locale.value = lang

  localStorage.setItem('locale', lang)
}

</script>

<template>
    <div class="min-h-screen bg-base-200">
        <Head :title="$page.props.title" />

        <!-- Sidebar and Content -->
        <div class="flex">
            <!-- Sidebar -->
            <div class="w-64 min-h-screen bg-base-100 shadow-lg hidden md:block">
                <ul class="menu p-4">
                    <li class="menu-title">Admin Panel</li>

                    <!-- Users -->
                    <li>
                        <Link href="/admin/users" :class="{ 'active': $page.url.startsWith('/admin/users') }">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            Users
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
