<script setup lang="ts">
defineProps({
    color: {
        type: String,
        default: 'bg-haven-white',
    },
});
</script>

<template>
    <div :class="[color, 'rounded-lg p-2 px-4']">
        <slot />
    </div>
</template>

<style scoped></style>
