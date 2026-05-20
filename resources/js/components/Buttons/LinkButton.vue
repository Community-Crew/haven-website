<script setup lang="ts">
withDefaults(
    defineProps<{
        link: string;
        name?: string;
        advanced?: boolean;
    }>(),
    {
        advanced: false,
    },
);
</script>

<template>
    <a
        :href="link"
        class="flex h-12 w-full cursor-pointer items-center justify-center rounded-2xl bg-haven-blue shadow-2xl transition-transform hover:scale-105"
    >
        <slot v-if="advanced" />
        <span v-else class="font-semibold text-white">{{ name }}</span>
    </a>
</template>

<style scoped></style>
