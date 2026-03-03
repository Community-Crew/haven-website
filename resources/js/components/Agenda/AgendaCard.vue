<script setup lang="ts">
import S3Image from '@/components/S3Image.vue';
import { Link } from '@inertiajs/vue3';
import { AgendaItem } from '@/types';

const props = defineProps<{
    item: AgendaItem;
}>();

const truncatedTitle = () => {
    if(props.item.title.length > 18){
        return props.item.title.slice(0, 16) + '...';
    }
    return props.item.title;
};
</script>

<template>
    <Link
        :href="route('agendas.items.show', [item.agenda.slug, item.slug])"
        class="relative grid h-auto rounded-2xl bg-haven-blue shadow-2xl sm:grid-cols-2 lg:grid-cols-6"
    >
        <div class="relative col-span-2">
            <S3Image
                :src="item.image_url"
                class="aspect-video h-full w-full bg-background object-cover not-lg:rounded-t-2xl md:rounded-l-2xl"
            />
            <div
                class="absolute right-0 bottom-4 z-1 h-10 content-center rounded-l-2xl bg-haven-blue/65 pr-3"
            >
                <span class="m-4 text-lg font-extrabold text-haven-white">
                    {{ truncatedTitle() }}
                </span>
            </div>
        </div>
        <div class="col-span-2 py-2 pl-2 lg:col-span-3">
            <p class="wrap-break-word">
                {{ item.short_description }}
            </p>
        </div>
        <div
            class="grid h-full grid-cols-1 justify-items-end py-2 pr-2 not-md:grid-cols-2"
        >
            <span
                class="ml-1 h-fit rounded-2xl bg-haven-light-blue/25 p-2 pr-2 shadow-sm not-lg:justify-self-start"
            >
                {{
                    new Date(item.start_date).toLocaleString(undefined, {
                        month: 'short',
                        day: '2-digit',
                    })
                }}
            </span>
        </div>
        <div class="absolute right-0 bottom-0 h-12 w-12">
            <S3Image :src="item.organisation.image_url" />
        </div>
    </Link>
</template>

<style scoped></style>
