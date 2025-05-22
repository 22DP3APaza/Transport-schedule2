<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    users: {
        type: Array,
        required: true,
    },
});

const users = ref(props.users);

const toggleAdmin = async (user) => {
    try {
        const response = await axios.put(`/admin/users/${user.id}/toggle-admin`);
        const index = users.value.findIndex(u => u.id === user.id);
        users.value[index].admin = !users.value[index].admin;
    } catch (error) {
        console.error('Error toggling admin status:', error);
    }
};

const deleteUser = async (user) => {
    if (!confirm('Are you sure you want to delete this user?')) return;

    try {
        await axios.delete(`/admin/users/${user.id}`);
        users.value = users.value.filter(u => u.id !== user.id);
    } catch (error) {
        console.error('Error deleting user:', error);
    }
};
</script>

<template>
    <Head :title="t('adminUsersTitle')" />

    <AdminLayout>
        <div class="p-4">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">{{ t('userManagement') }}</h1>
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
        <Link :href="`/admin/users/${user.id}/edit`" class="btn btn-primary btn-xs">
            {{ t('edit') }}
        </Link>
        <button class="btn btn-error btn-xs" @click="deleteUser(user)">
            {{ t('deleteUser') }}
        </button>
    </div>
</td>



                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
