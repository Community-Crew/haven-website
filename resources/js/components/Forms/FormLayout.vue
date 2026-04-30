<script setup lang="ts">
import { ref } from 'vue';

const props = defineProps<{
    form: any;
    action: string;
    method?: 'post' | 'put' | 'patch';
}>();

const isLoading = ref(false);

const submitForm = async () => {
    isLoading.value = true;
    try {
        const method = props.method || 'post';
        props.form[method](props.action, {
            onFinish: () => (isLoading.value = false),
        });
    } catch (error) {
        console.error('Submission failed:', error);
        isLoading.value = false;
    }
};
</script>

<template>
    <form
        class="mt-8 grid grid-cols-1 items-start gap-8 lg:grid-cols-2"
        @submit.prevent="submitForm"
    >
        <slot />

        <div class="lg:col-span-2">
            <button
                type="submit"
                :disabled="isLoading"
                class="rounded bg-blue-600 px-4 py-2 text-white disabled:opacity-50"
            >
                {{ isLoading ? 'Saving...' : 'Submit' }}
            </button>
        </div>
    </form>
</template>
