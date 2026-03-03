<script setup lang="ts">
import ContentCard from '@/components/ContentCard.vue';
import HeaderWave from '@/components/HeaderWave.vue';
import S3Image from '@/components/S3Image.vue';
import PublicAppLayout from '@/layouts/PublicAppLayout.vue';
import { AgendaItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import AgendaCard from '@/components/Agenda/AgendaCard.vue';

defineProps<{
    agendaItems: AgendaItem[];
}>();
</script>

<template>
    <Head title="Home"> </Head>
    <PublicAppLayout>
        <HeaderWave></HeaderWave>
        <div class="grid grid-cols-1 gap-8 sm:grid-cols-2">
            <ContentCard title="Agenda" position="start">
                <div class="grid grid-cols-1 gap-4">
                    <div v-if="agendaItems.length == 0" class="my-5 text-center">
                        <span class="text-6xl font-bold"> No upcoming agenda items!</span>
                    </div>
                    <AgendaCard
                        v-for="agendaItem in agendaItems"
                        :key="agendaItem.id"
                     :item="agendaItem"/>
                </div>
            </ContentCard>
            <ContentCard title="Latest News" position="end">
                <div class="my-5 text-center">
                    <span class="text-6xl font-bold">Work In progress </span>
                    <S3Image class="h-full w-full" />
                </div>
            </ContentCard>
        </div>
    </PublicAppLayout>
</template>
