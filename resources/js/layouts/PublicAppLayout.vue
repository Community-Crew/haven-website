<script setup lang="ts">
import Footer from '@/components/Footer.vue';
import PublicAppNavBar from '@/components/PublicAppLayout/PublicAppNavBar.vue';
</script>

<template>
    <div class="flex flex-col h-screen">
        <PublicAppNavBar class="flex-shrink-0" />
        <main class="flex-grow overflow-y-auto bg-haven-white">
            <slot />
        </main>
        <Footer class="flex-shrink-0 bg-haven-white" />
    </div>
</template>

<style scoped></style>
