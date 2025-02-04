<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <div class="relative flex flex-col justify-center h-screen overflow-hidden">
            <div class="w-full p-6 m-auto bg-white rounded-md shadow-md ring-2 ring-gray-800/50 lg:max-w-lg">
                <h1 class="text-3xl font-semibold text-center text-gray-700">Login</h1>
                <form class="space-y-4" @submit.prevent="submit">
                    <div>
                        <InputLabel for="email" value="Email" />
                        <TextInput id="email" type="email" class="w-full input input-bordered" v-model="form.email" required autofocus autocomplete="username" />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>
                    <div>
                        <InputLabel for="password" value="Password" />
                        <TextInput id="password" type="password" class="w-full input input-bordered" v-model="form.password" required autocomplete="current-password" />
                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>
                    <div class="flex justify-between">
                        <Link v-if="canResetPassword" :href="route('password.request')" class="text-xs text-gray-600 hover:underline hover:text-blue-600">Forgot Password?</Link>
                        <Link href="/register" class="text-xs text-gray-600 hover:underline hover:text-blue-600">Don't Have an Account?</Link>
                    </div>
                    <div class="mt-4 block">
                        <label class="flex items-center">
                            <Checkbox name="remember" v-model:checked="form.remember" />
                            <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">Remember me</span>
                        </label>
                    </div>
                    <div>
                        <PrimaryButton class="btn-neutral btn btn-block" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">Login</PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </GuestLayout>
</template>
