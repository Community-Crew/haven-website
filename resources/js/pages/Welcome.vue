<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

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
    <img class="p-8" src="https://www.graphicscycle.com/wp-content/uploads/2023/03/graphic-design-is-my-passion-gif-.gif">
    <div class="p-8">
        <a href="/auth/login/redirect">
            login
        </a>
    </div>

</template>
