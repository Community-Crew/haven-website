<script setup lang="ts">
import ContentCard from '@/components/ContentCard.vue';
import HeaderWave from '@/components/HeaderWave.vue';
import S3Image from '@/components/S3Image.vue';
import PublicAppLayout from '@/layouts/PublicAppLayout.vue';
import { Agenda, AgendaItem } from '@/types';
import '@vueup/vue-quill/dist/vue-quill.snow.css';

defineProps<{
    agenda: Agenda;
    agendaItem: AgendaItem;
}>();
</script>

<template>
    <PublicAppLayout>
        <HeaderWave />
        <div class="grid gap-8 md:grid-cols-2">
            <ContentCard
                position="start"
                :title="agendaItem.title"
                class="grid grid-cols-1 gap-8"
            >
                <div class="relative pb-2">
                    <S3Image
                        class="aspect-video rounded-2xl bg-background"
                        :src="agendaItem.image_url"
                    />
                    <div class="absolute right-6 bottom-8">
                        <S3Image
                            :src="agendaItem.organisation.image_url"
                            class="h-12 w-12 rounded-full"
                        />
                    </div>
                    <div
                        class="absolute bottom-8 left-0 z-1 h-12 w-fit content-center rounded-r-2xl bg-haven-blue/85 pl-10"
                    >
                        <span
                            class="m-4 text-lg font-extrabold text-haven-white lg:text-2xl"
                        >
                            {{ agendaItem.title }}
                        </span>
                    </div>
                </div>
                <div
                    class="relative flex items-start justify-between gap-4 p-4"
                >
                    <p
                        class="line-clamp-3 flex-1 pr-4 text-lg leading-relaxed font-normal text-haven-black not-md:hidden"
                    >
                        {{ agendaItem.short_description }}
                    </p>
                    <div class="h-full w-full shrink-0 md:w-1/3">
                        <div class="grid grid-cols-2 text-gray-700">
                            <span class="font-semibold">Start:</span>
                            <span>{{
                                new Date(
                                    agendaItem.start_date,
                                ).toLocaleDateString(undefined, {
                                    month: 'short',
                                    day: '2-digit',
                                    hour: '2-digit',
                                    minute: '2-digit',
                                })
                            }}</span>
                            <span class="font-semibold">End:</span>
                            <span>{{
                                new Date(
                                    agendaItem.end_date,
                                ).toLocaleDateString(undefined, {
                                    month: 'short',
                                    day: '2-digit',
                                    hour: '2-digit',
                                    minute: '2-digit',
                                })
                            }}</span>
                        </div>
                    </div>
                </div>
            </ContentCard>
            <ContentCard position="end" title="Description">
                <div class="text-haven-black" v-html="agendaItem.description" />
            </ContentCard>

            <ContentCard class="md:col-span-2" position="full">
                <div
                    class="flex min-h-[50vh] flex-col items-center justify-center gap-8 p-6 text-center"
                >
                    <p class="text-5xl font-extrabold text-haven-black">
                        Cool space for a feature that is in progress
                    </p>
                    <p class="text-xl text-haven-black md:text-3xl">
                        In the meanwhile you can enjoy the company of Ethel the
                        cat.
                    </p>

                    <S3Image class="mx-auto w-full max-w-lg" />
                </div>
            </ContentCard>
        </div>
    </PublicAppLayout>
</template>

<style scoped>
</style>
