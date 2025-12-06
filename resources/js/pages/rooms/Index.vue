<script setup lang="ts">
import ContentCard from '@/components/ContentCard.vue';
import HeaderWave from '@/components/HeaderWave.vue';
import S3Image from '@/components/S3Image.vue';
import PublicAppLayout from '@/layouts/PublicAppLayout.vue';
import { Room } from '@/types';
import { breakpointsTailwind, useBreakpoints } from '@vueuse/core';

const props = defineProps<{
    rooms: Room[];
}>();

const breakpoints = useBreakpoints(breakpointsTailwind);
const isLargeScreen = breakpoints.greaterOrEqual('2xl');

const getCardPosition = (index: number) => {
    if (isLargeScreen.value) {
        const remainder = index % 3;
        if (remainder === 0) return 'start';
        if (remainder === 2) return 'end';
        return 'middle';
    }
    return index % 2 === 0 ? 'start' : 'end';
};
</script>

<template>
    <PublicAppLayout>
        <HeaderWave />
        <div class="grid grid-cols-1 gap-8 md:grid-cols-2 2xl:grid-cols-3">
            <ContentCard
                v-for="(room, index) in props.rooms"
                v-bind:key="room.id"
                :position="getCardPosition(index)"
            >
                <div class="relative pb-2">
                    <S3Image
                        class="aspect-video rounded-2xl bg-background"
                        :src="room.image_path"
                    />
                    <div
                        class="absolute bottom-8 left-0 z-1 h-12 w-fit content-center rounded-r-2xl bg-haven-blue/85 pl-10"
                    >
                        <span
                            class="m-4 text-2xl font-extrabold text-haven-white"
                        >
                            {{ room.name }}
                        </span>
                    </div>
                </div>
                <div class="flex items-center justify-between gap-4 px-2 pt-2">
                    <p
                        class="line-clamp-3 flex-1 pl-2 text-lg leading-relaxed font-normal text-haven-black"
                    >
                        {{ room.description }}
                    </p>

                    <div class="shrink-0 pr-2">
                        <div
                            class="flex items-center justify-center rounded-full px-4 py-2 shadow-sm"
                            :class="room.status.background_color"
                        >
                            <span
                                :class="room.status.text_color"
                                class="text-sm font-bold tracking-wide uppercase"
                            >
                                {{ room.status.label }}
                            </span>
                        </div>
                    </div>
                </div>
            </ContentCard>
        </div>
    </PublicAppLayout>
</template>

<style scoped></style>
