<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const user = computed(() => page.props.auth.user);
console.log(user);
</script>

<template>
    <div v-if="!user">
        <a :href="route('authloginredirect')" class="hover:text-haven-white transition-colors duration-300">Login</a>
    </div>
    <div v-if="user">
        <a href="" class="hover:text-haven-white transition-colors duration-300">{{ user.name }}</a>
    </div>
</template>

<style scoped></style>
