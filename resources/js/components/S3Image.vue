<script setup lang="ts">
import { computed, ref, watch } from 'vue';

interface Props {
    src?: string | null;
    alt?: string;
    objectFit?: 'cover' | 'contain' | 'fill' | 'none' | 'scale-down';
}

const props = withDefaults(defineProps<Props>(), {
    src: null,
    alt: 'Image',
    objectFit: 'cover',
});

const isLoading = ref(true);
const hasError = ref(false);
const currentSrc = ref<string | null>(props.src);

const FALLBACK_URL = '/images/placeholder';

watch(
    () => props.src,
    (newVal) => {
        isLoading.value = true;
        hasError.value = false;

        currentSrc.value = newVal || FALLBACK_URL;
    },
    { immediate: true },
);

const onLoaded = (): void => {
    isLoading.value = false;
};

const onError = (): void => {
    if (currentSrc.value !== FALLBACK_URL) {
        console.warn('Image failed to load, swapping to placeholder.');
        currentSrc.value = FALLBACK_URL;
        isLoading.value = true;
    } else {
        isLoading.value = false;
        hasError.value = true;
    }
};

const fitClass = computed(() => {
    switch (props.objectFit) {
        case 'contain':
            return 'object-contain';
        case 'fill':
            return 'object-fill';
        case 'none':
            return 'object-none';
        case 'scale-down':
            return 'object-scale-down';
        default:
            return 'object-cover';
    }
});
</script>

<template>
    <div class="relative overflow-hidden">
        <div
            v-if="isLoading"
            class="absolute inset-0 z-10 animate-pulse bg-gray-300 dark:bg-gray-700"
        />

        <img
            v-if="currentSrc && !hasError"
            :src="currentSrc"
            :alt="alt"
            @load="onLoaded"
            @error="onError"
            class="h-full w-full transition-opacity duration-300"
            :class="[fitClass, isLoading ? 'opacity-0' : 'opacity-100']"
        />
    </div>
</template>

<style scoped></style>
