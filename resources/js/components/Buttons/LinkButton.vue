<script setup lang="ts">

defineProps<{
    link: string
    name: string
}>();
</script>

<template>
    <a
        :href="link"
        class="flex h-12 w-full cursor-pointer items-center justify-center rounded-2xl bg-haven-blue shadow-2xl transition-transform hover:scale-105"
    >
        <span class="font-semibold text-white">{{name}}</span>
    </a>
</template>

<style scoped></style>
