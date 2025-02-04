<script setup>
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'), {
        onFinish: () => form.reset('email'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Forgot Password" />

        <div class="relative flex flex-col justify-center h-screen overflow-hidden">
            <div class="w-full p-6 m-auto bg-white rounded-md shadow-md ring-2 ring-gray-800/50 lg:max-w-lg">
                <h1 class="text-3xl font-semibold text-center text-gray-700">Forgot Password</h1>
                <p class="text-center text-gray-500 mt-2">
                    Enter your email address, and we will send you a link to reset your password.
                </p>

                <form @submit.prevent="submit" class="space-y-4 mt-4">
                    <!-- Email Field -->
                    <div>
                        <label class="label">
                            <span class="text-base label-text">Email Address</span>
                        </label>
                        <input
                            type="email"
                            placeholder="Enter your email"
                            class="w-full input input-bordered"
                            v-model="form.email"
                            required
                            autofocus
                            autocomplete="username"
                        />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <!-- Submit Button -->
                    <div>
                        <button
                            class="btn-neutral btn btn-block"
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            Reset Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </GuestLayout>
</template>
