<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const page = usePage();

const user = computed(() => page.props.auth.user);

const isOpen = ref<boolean>(false);
const dropdownContainer = ref<HTMLDivElement | null>(null);

const toggleDropdown = (): void => {
    isOpen.value = !isOpen.value;
};

const handleOutsideClick = (event: MouseEvent): void => {
    if (
        dropdownContainer.value &&
        !dropdownContainer.value.contains(event.target as Node)
    ) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleOutsideClick);
});

onUnmounted(() => {
    document.removeEventListener('click', handleOutsideClick);
});
</script>

<template>
    <div v-if="!user">
        <a
            :href="route('auth.login.redirect')"
            class="transition-colors duration-300 hover:text-haven-white"
            >Login</a
        >
    </div>
    <div v-if="user" ref="dropdownContainer">
        <button
            @click="toggleDropdown"
            class="flex items-center transition-colors hover:text-white focus:outline-none"
        >
            <span>{{ user.name }}</span>
            <svg
                class="ml-2 h-4 w-4 transition-transform duration-200"
                :class="{ 'rotate-180': isOpen }"
                stroke="currentColor"
                viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 9l-7 7-7-7"
                ></path>
            </svg>
        </button>
        <transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div
                v-if="isOpen"
                class="ring-opacity-5 absolute right-5 z-50 mt-2 w-48 rounded-md bg-white py-1 shadow-lg ring-1 ring-black"
            >
                <a
                    :href="route('profile')"
                    class="block px-4 py-2 text-sm text-haven-green hover:bg-gray-100"
                    >Profile</a
                >
                <a
                    :href="route('authlogout')"
                    class="block px-4 py-2 text-sm text-haven-green hover:bg-gray-100"
                    >Logout</a
                >
            </div>
        </transition>
    </div>
</template>

<style scoped></style>
