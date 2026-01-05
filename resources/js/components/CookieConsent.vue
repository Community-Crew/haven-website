<script setup lang="ts">
import { ref, onMounted } from 'vue';

const isOpen = ref(false);

const CONSENT_KEY = 'mmmmh_koekjes';

onMounted(() => {
    // Check if user has already made a choice
    const consent = localStorage.getItem(CONSENT_KEY);

    // If no choice exists, show the banner
    if (consent === null) {
        isOpen.value = true;
    } else if (consent === 'accepted') {
        // If they previously accepted, load your tracking scripts here
        initializeAnalytics();
    }
});

const decline = () => {
    localStorage.setItem(CONSENT_KEY, 'declined');
    isOpen.value = false;
    // Optionally remove existing cookies here if needed
};


const initializeAnalytics = () => {
    if ((window as any).dataLayer) return; // Prevent double loading

    console.log('Analytics initialized');

    // Example: Load Google Analytics (Replace G-XXXXXXXXXX with your ID)
    /*
    const script = document.createElement('script');
    script.async = true;
    script.src = 'https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX';
    document.head.appendChild(script);

    (window as any).dataLayer = (window as any).dataLayer || [];
    function gtag(...args: any[]) { (window as any).dataLayer.push(args); }
    gtag('js', new Date());
    gtag('config', 'G-XXXXXXXXXX');
    */
};

const accept = () => {
    localStorage.setItem(CONSENT_KEY, 'accepted');
    isOpen.value = false;
    initializeAnalytics();
};

</script>

<template>
    <transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="transform translate-y-full opacity-0"
        enter-to-class="transform translate-y-0 opacity-100"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="transform translate-y-0 opacity-100"
        leave-to-class="transform translate-y-full opacity-0"
    >
        <div
            v-if="isOpen"
            class="fixed bottom-4 left-4 right-4 z-50 md:left-auto md:right-6 md:max-w-md"
        >
            <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-xl ring-1 ring-black/5">
                <div class="flex items-start justify-between">
                    <h3 class="text-lg font-bold text-gray-900">
                        🍪 Even over cookies
                    </h3>
                </div>

                <div class="mt-3 text-gray-600 text-sm leading-relaxed">
                    <p class="mb-2">
                        Geen zorgen, we gebruiken geen irritante trackers.
                    </p>
                    <p>
                        We gebruiken alleen <strong>functionele cookies</strong> om te zorgen dat je ingelogd blijft en de site werkt. Zoals beloofd in onze
                        <a href="/privacy-policy" class="text-blue-600 hover:underline font-medium">privacy policy</a>.
                    </p>
                </div>

                <div class="mt-5 flex flex-col sm:flex-row gap-3">
                    <button
                        @click="accept"
                        class="w-full justify-center rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-gray-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-900 transition-colors"
                    >
                        Helemaal prima
                    </button>

                    <!-- Optional 'Decline' button.
                         Since you only use functional cookies, legally you often don't strictly need a decline
                         for tracking, but it gives users a feeling of control. -->
                    <button
                        @click="decline"
                        class="w-full justify-center rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 transition-colors"
                    >
                        Liever niet
                    </button>
                </div>
            </div>
        </div>
    </transition>
</template>

<style scoped></style>
