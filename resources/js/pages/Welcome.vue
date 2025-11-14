<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PublicAppLayout from '@/layouts/PublicAppLayout.vue';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);
</script>

<template>
    <Head title="Welcome">
    </Head>
    <PublicAppLayout>
        <div class="bg-haven-white h-auto">
            TEST
        </div>
    </PublicAppLayout>
</template>
