<script setup lang="ts">
import { ref } from 'vue';
import NavBarProfile from '@/components/PublicAppLayout/NavBarProfile.vue';
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3';


const page = usePage();
const user = computed(() => page.props.auth.user);

const isMenuOpen = ref<boolean>(false);

const toggleMenu = (): void => {
    isMenuOpen.value = !isMenuOpen.value;
}

const closeMenu = (): void => {
    isMenuOpen.value = false;
}
</script>

<template>
    <nav class="bg-haven-blue text-haven-yellow shadow-lg">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">

                <!-- Brand/Logo -->
                <a href="#" class="text-xl font-bold">Haven Community</a>

                <!-- Desktop Navigation Links -->
                <div class="hidden md:flex items-center space-x-8">
                    <a :href="route('home')" class="hover:text-haven-white transition-colors duration-300">Home</a>
                    <a :href="route('wip')" class="hover:text-haven-white transition-colors duration-300">Agenda</a>
                    <a :href="route('rooms.index')" class="hover:text-haven-white transition-colors duration-300">Rooms</a>
                    <a :href="route('wip')" class="hover:text-haven-white transition-colors duration-300">Contact</a>
                    <div></div>
                    <NavBarProfile></NavBarProfile>
                </div>

                <div class="md:hidden flex items-center">
                    <button @click="toggleMenu" class="z-50 focus:outline-none" aria-label="Toggle menu">
                        <span class="w-6 h-6 flex flex-col justify-around">
              <span
                  class="block w-full h-0.5 bg-white transform transition duration-300 ease-in-out"
                  :class="{'rotate-45 translate-y-[5px]': isMenuOpen}"
              ></span>
                            <span
                                class="block w-full h-0.5 bg-white transition duration-300 ease-in-out"
                                :class="{'opacity-0': isMenuOpen}"
                            ></span>
                            <span
                                class="block w-full h-0.5 bg-white transform transition duration-300 ease-in-out"
                                :class="{'-rotate-45 -translate-y-[10px]': isMenuOpen}"
                            ></span>
                        </span>
                    </button>
                </div>

            </div>
        </div>

        <!-- Mobile Menu (Slide-in Overlay) -->
        <div
            class="fixed inset-0 bg-haven-blue bg-opacity-95 z-40 transform md:hidden transition-transform duration-300 ease-in-out"
            :class="isMenuOpen ? 'translate-x-0' : 'translate-x-full'"
        >
            <div class="flex flex-col items-center justify-center h-full space-y-8">
                <a href="" @click="closeMenu" class="text-haven-yellow text-3xl hover:text-haven-red">Home</a>
                <a href="" @click="closeMenu" class="text-haven-yellow text-3xl hover:text-haven-red">Agenda</a>
                <a href="" @click="closeMenu" class="text-haven-yellow text-3xl hover:text-haven-red">Rooms</a>
                <a href="" @click="closeMenu" class="text-haven-yellow text-3xl hover:text-haven-red">Contact</a>
                <a v-if="!user" :href="route('auth.login.redirect')" @click="closeMenu" class="text-haven-yellow text-3xl hover:text-haven-red">Login</a>
                <a v-if="user" :href="route('profile')" @click="closeMenu" class="text-haven-yellow text-3xl hover:text-haven-red">{{ user.name }}</a>
                <a v-if="user" :href="route('auth.logout')" @click="closeMenu" class="text-haven-yellow text-3xl hover:text-haven-red">Logout</a>
            </div>
        </div>
    </nav>
</template>

<style scoped></style>
