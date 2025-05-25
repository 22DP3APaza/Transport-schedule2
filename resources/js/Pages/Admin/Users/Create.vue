<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import FlashMessages from '@/Components/FlashMessages.vue';

const { t } = useI18n();
const { props: pageProps } = usePage();

// Initialize form data using Inertia's useForm helper
const form = useForm({
    username: '',
    email: '',
    password: '',
    password_confirmation: '', // Required for 'confirmed' validation rule
});

// Function to handle form submission
const submit = () => {
    form.post(route('admin.users.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'), // Clear password fields after submission
        onError: (errors) => {
            console.error('Form submission errors:', errors);
        },
    });
};
</script>

<template>
    <Head :title="t('createUserTitle')" />

    <AdminLayout>
        <div class="p-4">
            <FlashMessages :flash="pageProps.flash" />

            <h1 class="text-2xl font-bold mb-6">{{ t('createUser') }}</h1>

            <div class="bg-base-100 rounded-lg shadow p-6 max-w-lg mx-auto">
                <form @submit.prevent="submit">
                    <div class="mb-4">
                        <label for="username" class="block text-sm font-medium text-base-content mb-1">{{ t('username') }}</label>
                        <input
                            id="username"
                            type="text"
                            class="input input-bordered w-full"
                            v-model="form.username"
                            required
                            autofocus
                        />
                        <div v-if="form.errors.username" class="text-error text-sm mt-1">{{ form.errors.username }}</div>
                    </div>

                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-base-content mb-1">{{ t('email') }}</label>
                        <input
                            id="email"
                            type="email"
                            class="input input-bordered w-full"
                            v-model="form.email"
                            required
                        />
                        <div v-if="form.errors.email" class="text-error text-sm mt-1">{{ form.errors.email }}</div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="block text-sm font-medium text-base-content mb-1">{{ t('password') }}</label>
                        <input
                            id="password"
                            type="password"
                            class="input input-bordered w-full"
                            v-model="form.password"
                            required
                        />
                        <div v-if="form.errors.password" class="text-error text-sm mt-1">{{ form.errors.password }}</div>
                    </div>

                    <div class="mb-6">
                        <label for="password_confirmation" class="block text-sm font-medium text-base-content mb-1">{{ t('confirmPassword') }}</label>
                        <input
                            id="password_confirmation"
                            type="password"
                            class="input input-bordered w-full"
                            v-model="form.password_confirmation"
                            required
                        />
                        <div v-if="form.errors.password_confirmation" class="text-error text-sm mt-1">{{ form.errors.password_confirmation }}</div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary" :disabled="form.processing">
                            {{ t('create') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>

