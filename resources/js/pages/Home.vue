<script setup lang="ts">
import ContentCard from '@/components/ContentCard.vue';
import HeaderWave from '@/components/HeaderWave.vue';
import PublicAppLayout from '@/layouts/PublicAppLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Home"> </Head>
    <PublicAppLayout>
        <HeaderWave></HeaderWave>
        <div class="grid sm:grid-cols-2 gap-8 grid-cols-1">
            <ContentCard title="Agenda" position="start">
                <div class="text-center my-5">
                    <span class="text-6xl font-bold">Work In progress</span>
                </div>
            </ContentCard>
            <ContentCard title="Latest News" position="end">
                <div class="text-center my-5">
                    <span class="text-6xl font-bold">Work In progress <br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/></span>
                </div>
            </ContentCard>
        </div>
    </PublicAppLayout>
</template>
