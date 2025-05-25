<script setup>
import { computed } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Confirm Action',
    },
    message: {
        type: String,
        default: 'Are you sure you want to proceed with this action?',
    },
    confirmButtonText: {
        type: String,
        default: 'Confirm',
    },
    cancelButtonText: {
        type: String,
        default: 'Cancel',
    },
});

const emit = defineEmits(['confirm', 'cancel']);

// Compute if the modal should be visible
const isVisible = computed(() => props.show);

// Handle confirmation
const handleConfirm = () => {
    emit('confirm');
};

// Handle cancellation
const handleCancel = () => {
    emit('cancel');
};
</script>

<template>
    <div v-if="isVisible" class="modal modal-open">
        <div class="modal-box">
            <h3 class="font-bold text-lg">{{ title }}</h3>
            <p class="py-4">{{ message }}</p>
            <div class="modal-action">
                <button class="btn btn-ghost" @click="handleCancel">{{ cancelButtonText }}</button>
                <button class="btn btn-primary" @click="handleConfirm">{{ confirmButtonText }}</button>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Basic styling for the modal, assuming DaisyUI classes are available */
.modal {
    display: flex;
    align-items: center;
    justify-content: center;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5); /* Semi-transparent background */
    z-index: 9999; /* Ensure it's on top */
}

.modal-box {
    background-color: var(--fallback-b1, oklch(var(--b1) / 1)); /* DaisyUI background */
    padding: 1.5rem;
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    max-width: 90%;
    width: 400px; /* Max width for the modal content */
}

.modal-action {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    margin-top: 1rem;
}
</style>
