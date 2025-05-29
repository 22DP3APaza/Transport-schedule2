<script setup>
import { Head, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import NewsLayout from '@/Layouts/NewsLayout.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    news: {
        type: Object,
        required: true
    }
});

const { t, locale } = useI18n();
const showConfirmation = ref(false);
const selectedUrl = ref(null);
const pageInput = ref('');

const formatDate = (dateString) => {
    const date = new Date(dateString);
    const day = date.getDate();
    const month = date.toLocaleString(locale.value, { month: 'short' });
    const year = date.getFullYear();
    return `${day}${locale.value === 'lv' ? '.' : ''} ${month}${locale.value === 'lv' ? '.' : ''} ${year}`;
};

const openConfirmation = (url) => {
    selectedUrl.value = url;
    showConfirmation.value = true;
};

const goToOriginalSource = () => {
    window.open(selectedUrl.value, '_blank');
    showConfirmation.value = false;
};

const goToPage = (page) => {
    if (page >= 1 && page <= props.news.last_page) {
        router.get(route('news.index', { page }), {}, { preserveScroll: true });
    }
};

const handlePageInputSubmit = () => {
    const page = parseInt(pageInput.value);
    if (!isNaN(page) && page >= 1 && page <= props.news.last_page) {
        goToPage(page);
    }
    pageInput.value = '';
};

const paginationRange = computed(() => {
    const current = props.news.current_page;
    const last = props.news.last_page;
    const delta = 2;
    const range = [];
    const rangeWithDots = [];

    range.push(1);
    for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
        range.push(i);
    }
    if (last > 1) {
        range.push(last);
    }

    let l;
    for (let i of range) {
        if (l) {
            if (i - l === 2) {
                rangeWithDots.push(l + 1);
            } else if (i - l !== 1) {
                rangeWithDots.push('...');
            }
        }
        rangeWithDots.push(i);
        l = i;
    }

    return rangeWithDots;
});
</script>

<template>
    <Head :title="t('news')" />

    <NewsLayout>
        

        <div class="space-y-4">
            <div v-for="article in news.data" :key="article.id" class="card bg-base-100 shadow-xl hover:shadow-2xl transition-shadow">
                <div class="card-body">
                    <div class="flex items-center gap-4">
                        <div class="text-sm text-base-content/70">
                            {{ formatDate(article.date) }}
                        </div>
                    </div>
                    <h2 class="card-title">{{ article.title }}</h2>
                    <p class="text-base-content/80">{{ article.content }}</p>
                    <div class="card-actions justify-end">
                        <button @click="openConfirmation(article.source_url)" class="btn btn-primary btn-sm">
                            {{ t('readMore') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div class="flex flex-col items-center gap-4 mt-8" v-if="news.last_page > 1">
            <div class="join">
                <!-- Previous page button -->
                <button
                    class="join-item btn"
                    :disabled="news.current_page === 1"
                    @click="goToPage(news.current_page - 1)"
                >
                    «
                </button>

                <!-- Page numbers -->
                <button
                    v-for="page in paginationRange"
                    :key="page"
                    class="join-item btn"
                    :class="{
                        'btn-active': page === news.current_page,
                        'btn-disabled': page === '...'
                    }"
                    @click="page !== '...' && goToPage(page)"
                >
                    {{ page }}
                </button>

                <!-- Next page button -->
                <button
                    class="join-item btn"
                    :disabled="news.current_page === news.last_page"
                    @click="goToPage(news.current_page + 1)"
                >
                    »
                </button>
            </div>

            <!-- Page input -->
            <div class="flex items-center gap-2">
                <input
                    type="number"
                    v-model="pageInput"
                    class="input input-bordered input-sm w-20 text-center"
                    :placeholder="t('page')"
                    min="1"
                    :max="news.last_page"
                    @keyup.enter="handlePageInputSubmit"
                />
                <span class="text-sm text-base-content/70">/ {{ news.last_page }}</span>
                <button
                    class="btn btn-sm"
                    @click="handlePageInputSubmit"
                    :disabled="!pageInput || isNaN(parseInt(pageInput)) || parseInt(pageInput) < 1 || parseInt(pageInput) > news.last_page"
                >
                    {{ t('go') }}
                </button>
            </div>
        </div>

        <!-- Confirmation Modal -->
        <div v-if="showConfirmation" class="modal modal-open">
            <div class="modal-box">
                <h3 class="font-bold text-lg">{{ t('confirmation') }}</h3>
                <p class="py-4">{{ t('doYouWantToVisitRigasSatiksme') }}</p>
                <div class="modal-action">
                    <button class="btn btn-primary" @click="goToOriginalSource">{{ t('yes') }}</button>
                    <button class="btn" @click="showConfirmation = false">{{ t('no') }}</button>
                </div>
            </div>
            <div class="modal-backdrop" @click="showConfirmation = false">
                <button class="cursor-default">Close</button>
            </div>
        </div>
    </NewsLayout>
</template>

<style scoped>
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

/* Hide number input spinners */
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
input[type="number"] {
    -moz-appearance: textfield;
}
</style>
