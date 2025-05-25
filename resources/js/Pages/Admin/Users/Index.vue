<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import FlashMessages from '@/Components/FlashMessages.vue'; // Assuming this component exists or will be created

const { t } = useI18n();
const { props: pageProps } = usePage(); // Access page props including flash messages

const props = defineProps({
    users: {
        type: Array,
        required: true,
    },
});

// Use a ref to hold the users data, allowing reactivity for updates
const users = ref(props.users);

// State for the confirmation modal
const showConfirmModal = ref(false);
const userToDelete = ref(null);

// Function to open the confirmation modal
const confirmDelete = (user) => {
    userToDelete.value = user;
    showConfirmModal.value = true;
};

// Function to handle user deletion after confirmation
const deleteUser = async () => {
    if (!userToDelete.value) return;

    // Use Inertia's router.delete for deletion
    router.delete(route('admin.users.destroy', userToDelete.value.id), {
        onSuccess: () => {
            // Update the local users array after successful deletion
            users.value = users.value.filter(u => u.id !== userToDelete.value.id);
            userToDelete.value = null; // Clear the user to delete
            showConfirmModal.value = false; // Close the modal
        },
        onError: (errors) => {
            console.error('Error deleting user:', errors);
            userToDelete.value = null; // Clear the user to delete
            showConfirmModal.value = false; // Close the modal
            // Inertia's default error handling will display errors if they are passed as props
        },
    });
};

// Function to toggle admin status
const toggleAdmin = (user) => {
    // Use Inertia's router.put for updating
    router.put(route('admin.users.toggleAdmin', user.id), {}, {
        preserveScroll: true, // Keep scroll position after update
        onSuccess: () => {
            // Manually update the user's admin status in the local array
            // This is often needed because toggleAdmin is a JSON response, not an Inertia response
            const index = users.value.findIndex(u => u.id === user.id);
            if (index !== -1) {
                users.value[index].admin = !users.value[index].admin;
            }
        },
        onError: (errors) => {
            console.error('Error toggling admin status:', errors);
        },
    });
};
</script>

<template>
    <Head :title="t('adminUsersTitle')" />

    <AdminLayout>
        <div class="p-4">
            <FlashMessages :flash="pageProps.flash" />

            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">{{ t('userManagement') }}</h1>
                <div class="flex gap-2">
                    <Link :href="route('admin.users.index')" class="btn btn-ghost">
                        {{ t('home') }}
                    </Link>
                    <Link :href="route('admin.users.create')" class="btn btn-primary">
                        {{ t('createUser') }}
                    </Link>
                </div>
            </div>

            <div class="overflow-x-auto bg-base-100 rounded-lg shadow">
                <table class="table w-full">
                    <thead>
                        <tr>
                            <th>{{ t('id') }}</th>
                            <th>{{ t('username') }}</th>
                            <th>{{ t('email') }}</th>
                            <th>{{ t('admin') }}</th>
                            <th>{{ t('actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in users" :key="user.id">
                            <td>{{ user.id }}</td>
                            <td>{{ user.username }}</td>
                            <td>{{ user.email }}</td>
                            <td>
                                <input
                                    type="checkbox"
                                    class="toggle toggle-primary"
                                    :checked="user.admin"
                                    @change="toggleAdmin(user)"
                                    :title="t('toggleAdminStatus')"
                                />
                            </td>
                            <td>
                                <div class="flex gap-2">
                                    <Link :href="route('admin.users.edit', user.id)" class="btn btn-primary btn-xs">
                                        {{ t('edit') }}
                                    </Link>
                                    <button class="btn btn-error btn-xs" @click="confirmDelete(user)">
                                        {{ t('deleteUser') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="users.length === 0">
                            <td colspan="5" class="text-center py-4">{{ t('noUsersFound') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <ConfirmationModal
            :show="showConfirmModal"
            :title="t('confirmDeleteTitle')"
            :message="t('confirmDeleteMessage', { username: userToDelete?.username })"
            :confirmButtonText="t('delete')"
            :cancelButtonText="t('cancel')"
            @confirm="deleteUser"
            @cancel="showConfirmModal = false"
        />
    </AdminLayout>
</template>
