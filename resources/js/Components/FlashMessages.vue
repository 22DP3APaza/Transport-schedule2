<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    flash: {
        type: Object,
        default: () => ({}),
    },
});

// Reactive state for messages
const successMessage = ref(props.flash.success || null);
const errorMessage = ref(props.flash.error || null);

// Watch for changes in the flash prop to update messages
watch(() => props.flash.success, (newValue) => {
    successMessage.value = newValue;
    if (newValue) {
        setTimeout(() => successMessage.value = null, 5000); // Hide after 5 seconds
    }
});

watch(() => props.flash.error, (newValue) => {
    errorMessage.value = newValue;
    if (newValue) {
        setTimeout(() => errorMessage.value = null, 5000); // Hide after 5 seconds
    }
});

// Function to close messages manually
const closeMessage = (type) => {
    if (type === 'success') {
        successMessage.value = null;
    } else if (type === 'error') {
        errorMessage.value = null;
    }
};
</script>

<template>
    <div class="mb-4">
        <div v-if="successMessage" class="alert alert-success shadow-lg mb-4">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>{{ successMessage }}</span>
            </div>
            <button @click="closeMessage('success')" class="btn btn-sm btn-ghost">✕</button>
        </div>

        <div v-if="errorMessage" class="alert alert-error shadow-lg mb-4">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>{{ errorMessage }}</span>
            </div>
            <button @click="closeMessage('error')" class="btn btn-sm btn-ghost">✕</button>
        </div>
    </div>
</template>

<style scoped>
/* Basic styling for alerts, assuming DaisyUI classes are available */
.alert {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    border-radius: 0.5rem;
    font-size: 0.9rem;
}
.alert-success {
    background-color: #d4edda;
    color: #155724;
    border-color: #c3e6cb;
}
.alert-error {
    background-color: #f8d7da;
    color: #721c24;
    border-color: #f5c6cb;
}
.alert svg {
    margin-right: 0.5rem;
}
</style>
