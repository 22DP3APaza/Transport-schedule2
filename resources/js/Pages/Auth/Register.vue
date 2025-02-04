<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

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
</script>

<template>
    <div class="relative flex flex-col justify-center h-screen overflow-hidden">
        <Head title="Register" />
        <div class="w-full p-6 m-auto bg-white rounded-md shadow-md ring-2 ring-gray-800/50 lg:max-w-xl">
            <h1 class="text-3xl font-semibold text-center text-gray-700">Register</h1>
            <form class="space-y-4" @submit.prevent="submit">
                <div>
                    <InputLabel for="username" value="Username" />
                    <TextInput id="username" type="text" class="w-full input input-bordered" v-model="form.username" required autocomplete="username" />
                    <InputError class="mt-2" :message="form.errors.username" />
                </div>
                <div>
                    <InputLabel for="email" value="Email" />
                    <TextInput id="email" type="email" class="w-full input input-bordered" v-model="form.email" required autocomplete="username" />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>
                <div>
                    <InputLabel for="password" value="Password" />
                    <TextInput id="password" type="password" class="w-full input input-bordered" v-model="form.password" required autocomplete="new-password" />
                    <InputError class="mt-2" :message="form.errors.password" />
                </div>
                <div>
                    <InputLabel for="password_confirmation" value="Confirm Password" />
                    <TextInput id="password_confirmation" type="password" class="w-full input input-bordered" v-model="form.password_confirmation" required autocomplete="new-password" />
                    <InputError class="mt-2" :message="form.errors.password_confirmation" />
                </div>
                <div class="mt-4 flex items-center justify-between">
                    <Link :href="route('login')" class="text-xs text-gray-600 hover:underline hover:text-blue-600">Already registered?</Link>
                </div>
                <div>
                    <PrimaryButton class="btn btn-block" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">Register</PrimaryButton>
                </div>
                <div class="flex items-center w-full my-4">
                    <hr class="w-full" />
                    <p class="px-3">OR</p>
                    <hr class="w-full" />
                </div>
                <div class="my-6 space-y-2">
                    <button aria-label="Login with Google" type="button" class="flex items-center justify-center w-full p-2 space-x-4 border rounded-md focus:ring-2 focus:ring-offset-1 focus:ring-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" class="w-5 h-5 fill-current">
                            <path d="M16.318 13.714v5.484h9.078c-0.37 2.354-2.745 6.901-9.078 6.901-5.458 0-9.917-4.521-9.917-10.099s4.458-10.099 9.917-10.099c3.109 0 5.193 1.318 6.38 2.464l4.339-4.182c-2.786-2.599-6.396-4.182-10.719-4.182-8.844 0-16 7.151-16 16s7.156 16 16 16c9.234 0 15.365-6.49 15.365-15.635 0-1.052-0.115-1.854-0.255-2.651z"></path>
                        </svg>
                        <p>Login with Google</p>
                    </button>
                    <button aria-label="Login with GitHub" type="button" class="flex items-center justify-center w-full p-2 space-x-4 border rounded-md focus:ring-2 focus:ring-offset-1 focus:ring-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" class="w-5 h-5 fill-current">
                            <path d="M16 0.396c-8.839 0-16 7.167-16 16 0 7.073 4.584 13.068 10.937 15.183 0.803 0.151 1.093-0.344 1.093-0.772 0-0.38-0.009-1.385-0.015-2.719-4.453 0.964-5.391-2.151-5.391-2.151-0.729-1.844-1.781-2.339-1.781-2.339-1.448-0.989 0.115-0.968 0.115-0.968 1.604 0.109 2.448 1.645 2.448 1.645 1.427 2.448 3.744 1.74 4.661 1.328 0.14-1.031 0.557-1.74 1.011-2.135-3.552-0.401-7.287-1.776-7.287-7.907 0-1.751 0.62-3.177 1.645-4.297-0.177-0.401-0.719-2.031 0.141-4.235 0 0 1.339-0.427 4.4 1.641 1.281-0.355 2.641-0.532 4-0.541 1.36 0.009 2.719 0.187 4 0.541 3.043-2.068 4.381-1.641 4.381-1.641 0.859 2.204 0.317 3.833 0.161 4.235z"></path>
                        </svg>
                        <p>Login with GitHub</p>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
