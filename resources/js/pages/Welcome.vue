<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

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
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <img class="p-8" src="https://www.graphicscycle.com/wp-content/uploads/2023/03/graphic-design-is-my-passion-gif-.gif">
    <div class="p-8">
        <a href="/login/redirect">
            login
        </a>
    </div>

</template>
