<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import FlashMessages from '@/Components/FlashMessages.vue';

const { t } = useI18n();
const { props: pageProps } = usePage();

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
});

// Initialize form data with existing user details
const form = useForm({
    username: props.user.username,
    email: props.user.email,
});

// Function to handle form submission
const submit = () => {
    // Use router.put for updating existing resources
    form.put(route('admin.users.update', props.user.id), {
        onError: (errors) => {
            console.error('Form submission errors:', errors);
        },
    });
};
</script>

<template>
    <Head :title="t('editUserTitle')" />

    <AdminLayout>
        <div class="p-4">
            <FlashMessages :flash="pageProps.flash" />

            <h1 class="text-2xl font-bold mb-6">{{ t('editUser') }}</h1>

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

                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary" :disabled="form.processing">
                            {{ t('update') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>

