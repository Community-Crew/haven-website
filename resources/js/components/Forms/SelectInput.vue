<script setup lang="ts">
const model = defineModel<string | number>();

defineProps<{
    label: string;
    placeholder?: string;
    error?: string;
}>();
</script>

<template>
    <div>
        <label
            class="mb-1 block text-xs font-bold tracking-wide text-gray-700 uppercase"
        >
            {{ label }}
        </label>
        <select
            v-model="model"
            class="placeholder-gray/70 w-full rounded-lg border-none bg-white/25 px-4 py-3 text-haven-black shadow-sm focus:bg-white/30 focus:ring-2 focus:ring-white/50"
        >
            <slot />
        </select>
        <span v-if="error" class="text-xs text-red-500">{{ error }}</span>
    </div>
</template>
