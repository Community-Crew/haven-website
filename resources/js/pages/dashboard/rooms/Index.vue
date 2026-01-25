<script setup lang="ts">
import AdminDashboardLayout from '@/layouts/AdminDashboardLayout.vue';
import { Link } from '@inertiajs/vue3';
import ContentCard from '@/components/ContentCard.vue';
import {Room } from '@/types';

defineProps<{
    rooms: Room[];
}>();

</script>

<template>
    <AdminDashboardLayout>
        <div></div>
        <div class="pt-2">
            <ContentCard title="Reservations" class="m-6">
                <div class="flex flex-col">
                    <div class="relative">
                        <div
                            class="mt-8 rounded-3xl border-2 border-haven-light-blue bg-haven-light-blue/50"
                        >
                            <div
                                v-if="rooms.length > 0"
                                class="grid grid-cols-1 gap-x-8 gap-y-2"
                            >
                                <Link
                                    v-for="room in rooms"
                                    :key="room.id"
                                    :href="
                                        route(
                                            'admin.rooms.show',
                                            room.slug,
                                        )
                                    "
                                    class="place w-full rounded-2xl bg-haven-white/45 p-3 shadow"
                                >
                                    <div class="grid grid-cols-4">
                                        <p
                                            class="mr-2 line-clamp-1 truncate font-medium text-haven-black"
                                        >
                                            {{ room.name }}
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            {{ room.description }}
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            {{ room.location }}
                                        </p>
                                        <p
                                            class="w-fit rounded-sm p-1 shadow font-bold"
                                            :class="room.status.text_color + ' ' + room.status.background_color"
                                        >
                                            {{ room.status.label }}
                                        </p>
                                    </div>
                                </Link>
                            </div>

                            <div v-else class="py-16 text-center">
                                <p class="font-semibold text-gray-500">
                                    No rooms found.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </ContentCard>
        </div>
    </AdminDashboardLayout>
</template>

<style scoped></style>
