<script setup lang="ts">
import Footer from '@/components/Footer.vue';
import PublicAppNavBar from '@/components/PublicAppLayout/PublicAppNavBar.vue';
import ErrorPopup from '@/components/ErrorPopup.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import CookieConsent from '@/components/CookieConsent.vue';

const page = usePage();
const error = computed(()=> page.props.session)
</script>

<template>
    <div class="flex flex-col h-screen">
        <PublicAppNavBar class="flex-shrink-0" />
        <ErrorPopup class="fixed max-h-20 top-4 right-4 z-50" v-if="error" :message="error.toString()" type="error"></ErrorPopup>
        <main class="flex-grow overflow-y-auto bg-haven-white">
            <slot />
            <div class="w-full h-0 pb-[9%] shrink-0 pointer-events-none"/>
        </main>
        <Footer />
    </div>
    <CookieConsent/>
</template>

<style scoped></style>
