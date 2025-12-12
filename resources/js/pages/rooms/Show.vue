<script setup lang="ts">
import ContentCard from '@/components/ContentCard.vue';
import S3Image from '@/components/S3Image.vue';
import PublicAppLayout from '@/layouts/PublicAppLayout.vue';
import { Room } from '@/types';
import HeaderWave from '@/components/HeaderWave.vue';

const props = defineProps<{
    room: Room;
}>();
</script>

<template>
    <PublicAppLayout>
        <HeaderWave/>
        <div class="grid md:grid-cols-2 grid-cols-1 gap-8">
            <ContentCard position="start">
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
            <ContentCard position="end" title="Info">

            </ContentCard>
        </div>
        <div class="pt-8">
            <ContentCard position="full" title="Reservations">
            </ContentCard>
        </div>
    </PublicAppLayout>
</template>

<style scoped></style>
