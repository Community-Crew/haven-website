<script setup lang="ts">
import { PaginatedLink } from '@/types';
import { Link } from '@inertiajs/vue3';

defineProps<{
    links: PaginatedLink[];
}>();
</script>

<template>
    <div v-if="links.length > 3" class="-mb-1 flex flex-wrap">
        <template v-for="(link, key) in links" :key="key">
            <div
                v-if="link.url === null"
                class="mr-1 mb-1 rounded border px-4 py-3 text-sm leading-4 text-gray-400"
                v-html="link.label"
            />
            <Link
                v-else
                class="mr-1 mb-1 rounded border px-4 py-3 text-sm leading-4 hover:bg-white focus:border-indigo-500 focus:text-indigo-500"
                :class="{ 'bg-blue-700 text-white': link.active }"
                :href="link.url"
                v-html="link.label"
            />
        </template>
    </div>
</template>

<style scoped></style>
