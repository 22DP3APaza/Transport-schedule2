<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { onMounted } from 'vue';

const { t, locale } = useI18n();

// Load saved language preference
onMounted(() => {
    const savedLanguage = localStorage.getItem('language');
    if (savedLanguage) {
        locale.value = savedLanguage;
    }
});

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

// Language switching with persistence
const changeLanguage = (language) => {
    locale.value = language;
    localStorage.setItem('language', language);
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head :title="t('emailVerification')" />

        <div class="relative flex flex-col justify-center min-h-screen">
            <div class="w-full p-6 m-auto bg-white rounded-md shadow-md lg:max-w-lg">
                <!-- Language Switcher -->
                <div class="flex justify-end mb-4">
                    <div class="dropdown dropdown-end">
                        <label tabindex="0" class="btn btn-ghost btn-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 21l5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 016-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 01-3.814-5.801" />
                            </svg>
                            {{ locale.toUpperCase() }}
                        </label>
                        <ul tabindex="0" class="dropdown-content menu menu-sm bg-base-100 rounded-box shadow mt-2 z-[1]">
                            <li @click="changeLanguage('en')">
                                <a :class="{ 'active': locale === 'en' }">English</a>
                            </li>
                            <li @click="changeLanguage('lv')">
                                <a :class="{ 'active': locale === 'lv' }">Latviešu</a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="mb-4 text-sm text-gray-600">
                    {{ t('verifyEmailMessage') }}
                </div>

                <div
                    class="mb-4 text-sm font-medium text-green-600"
                    v-if="verificationLinkSent"
                >
                    {{ t('verificationLinkSent') }}
                </div>

                <form @submit.prevent="submit">
                    <div class="mt-4 flex items-center justify-between">
                        <PrimaryButton
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            {{ t('resendVerificationEmail') }}
                        </PrimaryButton>

                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        >
                            {{ t('logout') }}
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </GuestLayout>
</template>
