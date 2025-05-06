<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();

const changeLanguage = (language) => {
    locale.value = language;
};
</script>

<template>
    <Head :title="t('home')" />
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
            <div class="navbar bg-base-100"><a href="/bus" class="btn btn-ghost text-xl">{{ t('bus') }}</a></div>
            <div class="navbar bg-base-100"><a href="/trolleybus" class="btn btn-ghost text-xl">{{ t('trolleybus') }}</a></div>
            <div class="navbar bg-base-100"><a href="/tram" class="btn btn-ghost text-xl">{{ t('tram') }}</a></div>
            <div class="navbar bg-base-100"><a href="/train" class="btn btn-ghost text-xl">{{ t('train') }}</a></div>
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
                    <li>
                        <Link href="/settings">{{ t('settings') }}</Link>
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

        <input type="text" :placeholder="t('from')" class="input input-ghost w-full max-w-xs" style="border-bottom: 2px solid black;" />
        <input type="text" :placeholder="t('to')" class="input input-ghost w-full max-w-xs" style="border-bottom: 2px solid black;" />
    </div>
</template>
