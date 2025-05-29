<script setup>
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    totalNews: {
        type: Number,
        required: true
    }
});

const { t } = useI18n();
const quickUpdateLoading = ref(false);
const fullUpdateLoading = ref(false);
const displayedTotalNews = ref(props.totalNews);
const showToast = ref(false);
const toastMessage = ref('');
const toastType = ref('success'); // 'success' or 'error'
const showWarningModal = ref(false);
const pendingCommand = ref(null);

const showNotification = (message, type = 'success') => {
    toastMessage.value = message;
    toastType.value = type;
    showToast.value = true;
    setTimeout(() => {
        showToast.value = false;
    }, 3000);
};

const confirmUpdate = (command) => {
    pendingCommand.value = command;
    showWarningModal.value = true;
};

const startUpdate = async () => {
    showWarningModal.value = false;
    const command = pendingCommand.value;
    const isQuickUpdate = command === 'news:scrape-first';

    if (isQuickUpdate) {
        quickUpdateLoading.value = true;
    } else {
        fullUpdateLoading.value = true;
    }

    try {
        const response = await axios.post(route('admin.news.scrape'), { command });
        if (response.data.success) {
            displayedTotalNews.value = response.data.totalNews;
            showNotification(
                isQuickUpdate
                    ? t('quickUpdateSuccess')
                    : t('fullUpdateSuccess')
            );
        }
    } catch (error) {
        console.error('Error running command:', error);
        showNotification(t('updateError'), 'error');
    } finally {
        quickUpdateLoading.value = false;
        fullUpdateLoading.value = false;
        pendingCommand.value = null;
    }
};
</script>

<template>
    <Head :title="t('news')" />

    <AdminLayout>
        <!-- Toast Notification -->
        <div
            v-if="showToast"
            class="toast toast-top toast-end z-50"
        >
            <div
                class="alert"
                :class="{
                    'alert-success': toastType === 'success',
                    'alert-error': toastType === 'error'
                }"
            >
                <span>{{ toastMessage }}</span>
            </div>
        </div>

        <!-- Warning Modal -->
        <div v-if="showWarningModal" class="modal modal-open">
            <div class="modal-box">
                <h3 class="font-bold text-lg">{{ t('updateWarningTitle') }}</h3>
                <p class="py-4">{{ t('updateWarningMessage') }}</p>
                <div class="modal-action">
                    <button class="btn btn-primary" @click="startUpdate">{{ t('continue') }}</button>
                    <button class="btn" @click="showWarningModal = false">{{ t('cancel') }}</button>
                </div>
            </div>
            <div class="modal-backdrop" @click="showWarningModal = false">
                <button class="cursor-default">Close</button>
            </div>
        </div>

        <div class="p-8">
            <h1 class="text-3xl font-bold mb-8">{{ t('news') }}</h1>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-1 gap-6 mb-8">
                <div class="stats shadow">
                    <div class="stat">
                        <div class="stat-title">{{ t('totalNews') }}</div>
                        <div class="stat-value">{{ displayedTotalNews }}</div>
                    </div>
                </div>
            </div>

            <!-- Command Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Quick Update -->
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title">{{ t('scrapeFirstPage') }}</h2>
                        <p class="text-base-content/70">{{ t('quickUpdateDescription') }}</p>

                        <div class="card-actions justify-end items-center">
                            <span v-if="quickUpdateLoading" class="loading loading-spinner loading-md"></span>
                            <button
                                class="btn btn-primary"
                                :disabled="quickUpdateLoading || fullUpdateLoading"
                                @click="confirmUpdate('news:scrape-first')"
                            >
                                {{ t('scrapeFirstPage') }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Full Update -->
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title">{{ t('scrapeAllPages') }}</h2>
                        <p class="text-base-content/70">{{ t('fullUpdateDescription') }}</p>

                        <div class="card-actions justify-end items-center">
                            <span v-if="fullUpdateLoading" class="loading loading-spinner loading-md"></span>
                            <button
                                class="btn btn-secondary"
                                :disabled="quickUpdateLoading || fullUpdateLoading"
                                @click="confirmUpdate('news:scrape')"
                            >
                                {{ t('scrapeAllPages') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.toast {
    position: fixed;
    top: 1rem;
    right: 1rem;
}

.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
