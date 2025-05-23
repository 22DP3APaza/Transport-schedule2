<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { onMounted } from 'vue';

const { t, locale } = useI18n();

// Load saved language preference on component mount
onMounted(() => {
    const savedLanguage = localStorage.getItem('language');
    if (savedLanguage) {
        locale.value = savedLanguage;
    }
});

const form = useForm({
    username: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

// Language switching with persistence
const changeLanguage = (language) => {
    locale.value = language;
    localStorage.setItem('language', language);
};
</script>

<template>
    <div class="relative flex flex-col justify-center h-screen overflow-hidden">
        <Head :title="t('register')" />
        <div class="w-full p-6 m-auto bg-white rounded-md shadow-md ring-2 ring-gray-800/50 lg:max-w-xl">
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

            <h1 class="text-3xl font-semibold text-center text-gray-700">{{ t('register') }}</h1>
            <form class="space-y-4" @submit.prevent="submit">
                <div>
                    <InputLabel for="username" :value="t('username')" />
                    <TextInput
                        id="username"
                        type="text"
                        class="w-full input input-bordered"
                        v-model="form.username"
                        required
                        autocomplete="username"
                    />
                    <InputError class="mt-2" :message="form.errors.username" />
                </div>
                <div>
                    <InputLabel for="email" :value="t('email')" />
                    <TextInput
                        id="email"
                        type="email"
                        class="w-full input input-bordered"
                        v-model="form.email"
                        required
                        autocomplete="email"
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
                        autocomplete="new-password"
                        :placeholder="t('passwordPlaceholder')"
                    />
                    <InputError class="mt-2" :message="form.errors.password" />
                </div>
                <div>
                    <InputLabel for="password_confirmation" :value="t('confirmPassword')" />
                    <TextInput
                        id="password_confirmation"
                        type="password"
                        class="w-full input input-bordered"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                        :placeholder="t('passwordPlaceholder')"
                    />
                    <InputError class="mt-2" :message="form.errors.password_confirmation" />
                </div>
                <div class="mt-4 flex items-center justify-between">
                    <Link
                        :href="route('login')"
                        class="text-xs text-gray-600 hover:underline hover:text-blue-600"
                    >
                        {{ t('alreadyRegistered') }}
                    </Link>
                </div>
                <div>
                    <PrimaryButton
                        class="btn btn-block"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        {{ t('register') }}
                    </PrimaryButton>
                </div>

            </form>
        </div>
    </div>
</template>
