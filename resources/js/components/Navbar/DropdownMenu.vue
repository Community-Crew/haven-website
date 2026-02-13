<script setup lang="ts">
import { onMounted, onUnmounted, defineProps, reactive, ref } from 'vue';

interface DropdownsState {
    activeId: string | null;
}

const dropdownsState = reactive<DropdownsState>({ activeId: null });
const dropdownRef = ref<HTMLElement | null>(null);

const props = defineProps<{
    title: string;
    id: string;
}>();

const toggleDropdown = (): void => {
    dropdownsState.activeId = dropdownsState.activeId === props.id ? null : props.id;
};

const handleOutsideClick = (event: MouseEvent): void => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
        dropdownsState.activeId = null;
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
    <div class="relative inline-block" ref="dropdownRef">
        <button
            @click.stop="toggleDropdown"
            class="flex items-center transition-colors hover:text-white focus:outline-none"
        >
            <span>{{ title }}</span>
            <svg
                class="ml-2 h-4 w-4 transition-transform duration-200"
                :class="{ 'rotate-180': dropdownsState.activeId === props.id }"
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
                v-if="dropdownsState.activeId === props.id"
                class="absolute right-0 z-50 w-48 mt-2 rounded-md bg-white shadow-lg ring-1 ring-black"
            >
                <slot />
            </div>
        </transition>
    </div>
</template>

<style scoped>
.relative {
    position: relative;
}

.absolute {
    position: absolute;
}
</style>
