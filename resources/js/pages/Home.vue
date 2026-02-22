<script setup lang="ts">
import ContentCard from '@/components/ContentCard.vue';
import HeaderWave from '@/components/HeaderWave.vue';
import S3Image from '@/components/S3Image.vue';
import PublicAppLayout from '@/layouts/PublicAppLayout.vue';
import { AgendaItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

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
                    <!--                                            :href="route('agendas.items.show', [agenda.slug, item.slug])"-->
                    <Link
                        v-for="agendaItem in agendaItems"
                        :key="agendaItem.id"
                        :href="route('agendas.items.show', [agendaItem.agenda.slug, agendaItem.slug])"
                        class="grid h-auto grid-cols-1 gap-4 rounded-2xl bg-haven-blue/75 sm:grid-cols-2 lg:grid-cols-6 shadow-2xl"
                    >
                        <div
                            class="relative col-span-2 sm:col-span-2 md:col-span-2"
                        >
                            <S3Image
                                :src="agendaItem.image_url"
                                class="aspect-video h-full w-full md:rounded-l-2xl not-lg:rounded-t-2xl bg-background object-cover"
                            />
                            <div
                                class="absolute right-0 bottom-4 z-1 h-10 content-center rounded-l-2xl bg-haven-blue/65 pr-3"
                            >
                                <span
                                    class="m-4 text-lg font-extrabold text-haven-white"
                                >
                                    {{ agendaItem.title }}
                                </span>
                            </div>
                        </div>
                        <div
                            class="col-span-1 py-2 pl-2 sm:col-span-2 md:col-span-3"
                        >
                            <p class="wrap-break-word">
                                {{ agendaItem.short_description }}
                            </p>
                        </div>
                        <div
                            class="grid grid-cols-1 justify-items-end py-2 pr-2 h-full"
                        >
                            <span class="pr-2">
                                {{
                                    new Date(
                                        agendaItem.start_date,
                                    ).toLocaleString(undefined, {
                                        month: 'short',
                                        day: '2-digit',
                                    })
                                }}
                            </span>
                            <S3Image
                                :src="agendaItem.organisation.image_url"
                                class="aspect-square max-h-1/2 rounded-full"
                            />
                        </div>
                    </Link>
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
