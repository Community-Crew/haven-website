<script setup lang="ts">
import ContentCard from '@/components/ContentCard.vue';
import S3Image from '@/components/S3Image.vue';
import AdminDashboardLayout from '@/layouts/AdminDashboardLayout.vue';
import { AgendaItem } from '@/types';
import { router } from '@inertiajs/vue3';
import { getCurrentInstance } from 'vue';


const app = getCurrentInstance();
const route = app?.appContext.config.globalProperties.route;

const props = defineProps<{
    agendaItem: AgendaItem;
}>();

const deleteAgendaItem = () => {
    if (confirm('Are you sure you want to delete this agenda item?')) {
        if (!route) return;
        router.delete(route('admin.agendas.items.destroy', [props.agendaItem.agenda.slug, props.agendaItem.slug]), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <AdminDashboardLayout>
        <div class="mt-8 grid grid-cols-2 gap-8">
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
                    <div
                        class="absolute bottom-8 left-0 z-1 h-12 w-fit content-center rounded-r-2xl bg-haven-blue/85 pl-10"
                    >
                        <span
                            class="m-4 text-2xl font-extrabold text-haven-white"
                        >
                            {{ agendaItem.title }}
                        </span>
                    </div>
                </div>
                <div class="flex items-start justify-between gap-4 p-4">
                    <p
                        class="line-clamp-3 flex-1 pr-4 text-lg leading-relaxed font-normal text-haven-black"
                    >
                        {{ agendaItem.short_description }}
                    </p>
                    <div class="w-1/3 shrink-0">
                        <div class="grid grid-cols-2 text-gray-700">
                            <span class="font-semibold">Start:</span>
                            <span>{{
                                agendaItem.start_date.slice(0, 16)
                            }}</span>
                            <span class="font-semibold">End:</span>
                            <span>{{ agendaItem.end_date.slice(0, 16) }}</span>
                            <span class="font-semibold">Organisation:</span>
                            <span>{{ agendaItem.organisation.name }}</span>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-8 text-gray-700">
                    <button
                        @click="deleteAgendaItem"
                        class="flex h-12 w-full cursor-pointer items-center justify-center rounded-2xl bg-haven-red shadow-2xl transition-transform hover:scale-105"
                    >
                        <span class="font-semibold text-white"
                            >Delete</span
                        >
                    </button>
                    <button
                        class="flex h-12 w-full cursor-pointer items-center justify-center rounded-2xl bg-haven-blue shadow-2xl transition-transform hover:scale-105"
                    >
                        <span class="font-semibold text-white"
                        >Edit (WIP)</span
                        >
                    </button>
                </div>
            </ContentCard>
            <ContentCard position="end" title="Description">
                <div class="text-black" v-html="agendaItem.description"></div>
            </ContentCard>
            <ContentCard class="col-span-2" position="full">
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
    </AdminDashboardLayout>
</template>

<style scoped></style>
