<script setup lang="ts">
import PublicAppLayout from '@/layouts/PublicAppLayout.vue';
import HeaderWave from '@/components/HeaderWave.vue';
import S3Image from '@/components/S3Image.vue';
</script>

<template>
    <PublicAppLayout>
        <HeaderWave />
        <div class="flex flex-col items-center justify-center text-center min-h-[50vh] gap-8 p-6">
            <p class="text-5xl md:text-9xl font-extrabold text-haven-black">
                Work In Progress
            </p>
            <p class="text-xl md:text-3xl text-haven-black">
                In the meanwhile you can enjoy the company of Ethel the cat.
            </p>

            <S3Image class="mx-auto w-full max-w-lg" />

        </div>

    </PublicAppLayout>
</template>

<style scoped></style>
