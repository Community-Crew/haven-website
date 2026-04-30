<script setup lang="ts">
defineProps<{
    cols: string;
}>();
</script>

<template>
    <div
        :class="'grid grid-cols-' + cols + ' content-center justify-items-center gap-8 p-6'"
    >
        <slot />
    </div>
</template>

<style scoped></style>
