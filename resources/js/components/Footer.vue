<script setup lang="ts">
const appVersion = import.meta.env.VITE_APP_VERSION;
</script>

<template>
    <footer class="relative z-10">
        <div class="absolute bottom-full left-0 w-full leading-none pointer-events-none">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 1440 200"
                class="h-auto w-full"
                preserveAspectRatio="none"
            >
                <path class="pointer-events-auto fill-haven-blue"
                    d="M0,120L60,110C120,100,240,80,360,90C480,100,600,140,720,140C840,140,960,100,1080,96.7C1200,93,1320,127,1380,140L1440,153.3L1440,200L1380,200C1320,200,1200,200,1080,200C960,200,840,200,720,200C600,200,480,200,360,200C240,200,120,200,60,200L0,200Z"
                ></path>
            </svg>
        </div>
        <p
            class="hidden sm:flex absolute right-6 bottom-4 font-mono text-xs text-white/50 select-none"
        >
            {{ appVersion }}
        </p>
        <div
            class="hidden absolute bottom-8 left-6 sm:flex items-center gap-x-1 font-mono text-xs text-white/50 select-none"
        >
            <a class="font-semibold text-white/75 hover:underline" :href="route('privacy-policy')">Privacy Policy</a>
        </div>
        <div
            class="hidden absolute bottom-4 left-6 sm:flex items-center gap-x-1 font-mono text-xs text-white/50 select-none"
        >
            <p>Made with</p>
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                class="h-4 w-4 text-haven-red"
            >
                <path
                    fill="currentColor"
                    d="M16.688 3c-1.936 0-3.631.833-4.688 2.24C10.943 3.832 9.248 3 7.313 3A5.82 5.82 0 0 0 1.5 8.813c0 6.562 9.73 11.874 10.145 12.093a.75.75 0 0 0 .71 0c.415-.22 10.145-5.531 10.145-12.093A5.82 5.82 0 0 0 16.688 3M12 19.388c-1.712-.998-9-5.542-9-10.576A4.32 4.32 0 0 1 7.313 4.5c1.823 0 3.354.971 3.993 2.531a.75.75 0 0 0 1.388 0c.64-1.563 2.17-2.531 3.993-2.531A4.32 4.32 0 0 1 21 8.813c0 5.025-7.29 9.576-9 10.574"
                ></path>
            </svg>
            <p>in Eindhoven, By</p>
            <a class="font-semibold text-white/75 hover:underline" href="https://github.com/Kroffie">Tim</a>
        </div>

    </footer>
</template>

<style scoped></style>
