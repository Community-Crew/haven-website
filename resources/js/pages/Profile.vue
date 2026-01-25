<script setup lang="ts">
import ContentCard from '@/components/ContentCard.vue';
import PublicAppLayout from '@/layouts/PublicAppLayout.vue';

import Pagination from '@/components/Pagination/Pagination.vue';
import { Paginator, Reservation, Unit } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const user = computed(() => page.props.auth.user);

const created_at = new Date(user.value.created_at).toISOString().split('T')[0];

const props = defineProps<{
    unit: Unit;
    groups: string;
    reservations: Paginator<Reservation>;
}>();
</script>

<template>
    <PublicAppLayout>
        <div class="h-10" />
        <div class="mx-auto">
            <div class="mb-12 grid grid-cols-1 gap-6 md:grid-cols-2 md:gap-8">
                <ContentCard title="Profile" position="start">
                    <div class="text-haven-black">
                        <h2 class="mt-4 text-2xl font-bold">{{ user.name }}</h2>
                        <p class="text-haven-blue/70">{{ user.email }}</p>
                    </div>
                    <hr class="my-6 border-t border-haven-blue/20" />
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold">Status:</span>
                            <span
                                class="rounded-full bg-green-200 px-2.5 py-0.5 text-sm font-medium text-green-800"
                                >{{ 'Validated' }}</span
                            >
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="font-semibold">Member Since:</span>
                            <span class="text-brand-dark-blue/80">{{
                                created_at
                            }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="font-semibold">Groups:</span>
                            <span class="text-brand-dark-blue/80">{{
                                groups
                            }}</span>
                        </div>
                    </div>
                </ContentCard>

                <ContentCard title="Unit" position="end">
                    <div class="text-haven-black">
                        <h2 class="mt-4 text-2xl font-bold">
                            {{ props.unit.name }}
                        </h2>
                    </div>
                    <hr class="my-6 border-t border-haven-blue/20" />
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold">Building:</span>
                            <span
                                class="rounded-full bg-green-200 px-2.5 py-0.5 text-sm font-medium text-green-800"
                                >{{ props.unit.building }}</span
                            >
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="font-semibold">Floor:</span>
                            <span class="text-brand-dark-blue/80">{{
                                props.unit.floor.toString().padStart(2, '0')
                            }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="font-semibold">Unit:</span>
                            <span class="text-brand-dark-blue/80">{{
                                props.unit.unit.padStart(2, '0')
                            }}</span>
                        </div>
                        <div
                            v-if="props.unit.subunit"
                            class="flex items-center justify-between"
                        >
                            <span class="font-semibold">Room:</span>
                            <span class="text-brand-dark-blue/80">{{
                                props.unit.subunit
                            }}</span>
                        </div>
                    </div>
                </ContentCard>

                <ContentCard
                    title="Upcoming reservations"
                    position="full"
                    class="md:col-span-2"
                >
                    <div
                        v-if="reservations.data.length > 0"
                        class="mt-4 grid gap-x-8 gap-y-2 md:grid-cols-2"
                    >
                        <div
                            v-for="reservation in reservations.data"
                            :key="reservation.id"
                            class="place w-full rounded-2xl bg-haven-white/45 p-3 shadow"
                        >
                            <div class="grid grid-cols-3">
                                <p
                                    class="mr-2 line-clamp-1 truncate font-medium text-haven-black"
                                >
                                    {{ reservation.name }}
                                </p>
                                <p class="text-haven-black">
                                    {{ reservation.room.name }}
                                </p>
                                <p
                                    :class="
                                        reservation.status.background_color +
                                        ' ' +
                                        reservation.status.text_color
                                    "
                                    class="w-20 rounded-2xl p-1 text-center text-sm font-bold"
                                >
                                    {{ reservation.status.label }}
                                </p>
                            </div>
                            <hr class="my-2 border-t border-haven-blue/20" />
                            <div class="grid grid-cols-3 text-sm">
                                <p class="text-gray-500">
                                    #{{
                                        reservation.id
                                            .toString()
                                            .padStart(5, '0')
                                    }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    {{
                                        new Date(
                                            reservation.start_at,
                                        ).toLocaleString([], {
                                            day: '2-digit',
                                            month: '2-digit',
                                            year: 'numeric',
                                            hour: '2-digit',
                                            minute: '2-digit',
                                        })
                                    }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    {{
                                        new Date(
                                            reservation.end_at,
                                        ).toLocaleString([], {
                                            day: '2-digit',
                                            month: '2-digit',
                                            year: 'numeric',
                                            hour: '2-digit',
                                            minute: '2-digit',
                                        })
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div v-else class="py-16 text-center">
                        <p class="font-semibold text-gray-500">
                            No reservations found.
                        </p>
                    </div>
                    <Pagination
                        class="justify-center pt-4"
                        :links="reservations.links"
                    />
                </ContentCard>
            </div>
        </div>
    </PublicAppLayout>
</template>

<style scoped></style>
