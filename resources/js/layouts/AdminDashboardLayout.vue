<script setup lang="ts">
import DashboardNavBar from '@/components/AdminDashboardLayout/DashboardNavBar.vue';
import ErrorPopup from '@/components/ErrorPopup.vue';
import Footer from '@/components/Footer.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const error = computed(() => page.props.session);
</script>

<template>
    <div class="flex h-screen flex-col">
        <DashboardNavBar class="flex-shrink-0" />
        <ErrorPopup
            class="fixed top-4 right-4 z-50 max-h-20"
            v-if="error"
            :message="error.toString()"
            type="error"
        ></ErrorPopup>
        <main class="flex-grow overflow-y-auto bg-haven-white">
            <slot />
        </main>
        <footer class="w-full flex-shrink-0">
            <Footer class="flex-shrink-0" />
        </footer>
    </div>
</template>

<style scoped></style>
