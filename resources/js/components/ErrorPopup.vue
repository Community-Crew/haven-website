<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';

type AlertType = 'success' | 'error' | 'warning' | 'info';

interface Props {
    message: string;
    type?: AlertType;
    duration?: number;
}

const props = withDefaults(defineProps<Props>(), {
    type: 'success',
    duration: 5000,
});

const isVisible = ref<boolean>(true);

const typeClasses = computed(() => {
    switch (props.type) {
        case 'error':
            return 'border-red-300 bg-red-100 text-red-800';
        case 'warning':
            return 'border-yellow-300 bg-yellow-100 text-yellow-800';
        case 'info':
            return 'border-blue-300 bg-blue-100 text-blue-800';
        case 'success':
        default:
            return 'border-green-300 bg-green-100 text-green-800';
    }
});

const focusRingClass = computed(() => {
    switch (props.type) {
        case 'error':
            return 'focus:ring-red-500';
        case 'warning':
            return 'focus:ring-yellow-500';
        case 'info':
            return 'focus:ring-blue-500';
        default:
            return 'focus:ring-green-500';
    }
});

function hide() {
    isVisible.value = false;
}

onMounted(() => {
    if (props.duration > 0) {
        setTimeout(() => {
            hide();
        }, props.duration);
    }
});
</script>

<template>
    <transition name="fade">
        <div
            v-if="isVisible"
            :class="[
                'fixed right-5 bottom-5 z-50 flex max-w-md min-w-[300px] items-center justify-between rounded-lg border p-4 shadow-lg',
                typeClasses,
            ]"
            role="alert"
        >
            <span class="font-medium">{{ message }}</span>

            <button
                type="button"
                class="ml-4 rounded-md p-1 opacity-70 transition hover:opacity-100 focus:ring-2 focus:outline-none"
                :class="focusRingClass"
                @click="hide"
                aria-label="Close"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>
            </button>
        </div>
    </transition>
</template>

<style scoped>
</style>
