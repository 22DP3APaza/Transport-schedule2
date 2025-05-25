<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { onMounted, ref } from 'vue';

const { t, locale } = useI18n();
const currentTheme = ref('light'); // Add theme state

// Initialize form with default values
const form = useForm({
    email: '',
    password: '',
    remember: false,
});

// Load saved preferences and "remember me" state on component mount
onMounted(() => {
    // Load language preference from local storage
    const savedLanguage = localStorage.getItem('language');
    if (savedLanguage) {
        locale.value = savedLanguage;
    }

    // Load theme preference from local storage
    const savedTheme = localStorage.getItem('theme') || 'light';
    currentTheme.value = savedTheme;
    document.documentElement.setAttribute('data-theme', savedTheme);

    // Load "remember me" state and pre-fill email if remembered
    const savedRememberMe = localStorage.getItem('rememberMe');
    if (savedRememberMe) {
        try {
            const rememberMeData = JSON.parse(savedRememberMe);
            // Check if rememberMeData and remember property exist and is true
            if (rememberMeData && rememberMeData.remember) {
                form.remember = true; // Set the remember checkbox to true
                form.email = rememberMeData.email || ''; // Pre-fill the email field
            }
        } catch (e) {
            // Log error if parsing fails and clear potentially corrupted data
            console.error("Error parsing rememberMe data from localStorage:", e);
            localStorage.removeItem('rememberMe');
        }
    }
});

// Theme toggle function: switches between 'light' and 'dark' themes
const toggleTheme = () => {
    currentTheme.value = currentTheme.value === 'light' ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', currentTheme.value);
    localStorage.setItem('theme', currentTheme.value); // Persist theme preference
};

// Define component props
defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

// Submit function for the login form
const submit = () => {
    form.post(route('login'), {
        // Callback executed after the form submission finishes (success or error)
        onFinish: () => form.reset('password'), // Reset password field after submission
        // Callback executed only on successful form submission
        onSuccess: () => {
            if (form.remember) {
                // If "remember me" is checked, save email and remember state to local storage
                localStorage.setItem('rememberMe', JSON.stringify({
                    email: form.email,
                    remember: form.remember
                }));
            } else {
                // If "remember me" is not checked, remove any previously saved data
                localStorage.removeItem('rememberMe');
            }
        }
    });
};

// Language switching with persistence
const changeLanguage = (language) => {
    locale.value = language;
    localStorage.setItem('language', language); // Persist language preference
};
</script>

<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-base-100">
        <Head :title="t('login')" />

        <button
            class="absolute top-4 right-4 btn btn-ghost btn-circle"
            @click="toggleTheme"
            :title="t('toggleTheme')"
        >
            <svg v-if="currentTheme === 'light'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
            </svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
            </svg>
        </button>

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-base-100 shadow-md overflow-hidden sm:rounded-lg">
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

            <h1 class="text-3xl font-semibold text-center text-base-content">{{ t('login') }}</h1>
            <form class="space-y-4 mt-4" @submit.prevent="submit">
                <div>
                    <InputLabel for="email" :value="t('email')" />
                    <TextInput
                        id="email"
                        type="email"
                        class="w-full input input-bordered"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                        :placeholder="t('emailPlaceholder')"
                    />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>
                <div>
                    <InputLabel for="password" :value="t('password')" />
                    <TextInput
                        id="password"
                        type="password"
                        class="w-full input input-bordered"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        :placeholder="t('passwordPlaceholder')"
                    />
                    <InputError class="mt-2" :message="form.errors.password" />
                </div>
                <div class="flex justify-between">
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-xs text-base-content hover:underline hover:text-primary"
                    >
                        {{ t('forgotPassword') }}
                    </Link>
                    <Link
                        href="/register"
                        class="text-xs text-base-content hover:underline hover:text-primary"
                    >
                        {{ t('noAccount') }}
                    </Link>
                </div>
                <div class="mt-4 block">
                    <label class="flex items-center">
                        <Checkbox name="remember" v-model:checked="form.remember" />
                        <span class="ms-2 text-sm text-base-content">
                            {{ t('rememberMe') }}
                        </span>
                    </label>
                </div>
                <div>
                    <PrimaryButton
                        class="btn-primary btn-block"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        {{ t('login') }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </div>
</template>
