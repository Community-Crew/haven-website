<script setup lang="ts">

defineProps<{
    action: () => void;
    name: string;
}>();
</script>

<template>
    <button
        @click="action"
        type="button"
        class="flex h-12 w-full cursor-pointer items-center justify-center rounded-2xl bg-haven-blue shadow-2xl transition-transform hover:scale-105"
    >
        <span class="font-semibold text-white">{{name}}</span>
    </button>
</template>

<style scoped></style>
