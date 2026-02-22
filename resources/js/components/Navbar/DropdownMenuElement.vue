<script setup lang="ts">
defineProps<{
    title: string;
    route: string;
}>();
</script>

<template>
    <a
        :href="route"
        class="block px-4 py-2 text-sm text-haven-green hover:bg-gray-100 z-0"
        >{{ title }}</a
    >
</template>

<style scoped></style>
