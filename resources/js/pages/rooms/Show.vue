<script setup lang="ts">
import ContentCard from '@/components/ContentCard.vue';
import HeaderWave from '@/components/HeaderWave.vue';
import ReservationOverlay from '@/components/Reservations/ReservationOverlay.vue';
import ReservationPolicyShow from '@/components/Reservations/ReservationPolicyShow.vue';
import S3Image from '@/components/S3Image.vue';
import PublicAppLayout from '@/layouts/PublicAppLayout.vue';
import { Organisation, Reservation, ReservationPolicy, Room } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const page = usePage();

defineProps<{
    room: Room;
    reservations: Reservation[];
    weeklyPolicies: ReservationPolicy[];
    userOrganisations: Organisation[];
}>();

const showReservationModal = ref(false);

const formatTime = (dateStr: string) => {
    return new Date(dateStr).toLocaleTimeString('nl-NL', {
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString('nl-NL');
};
</script>

<template>
    <ReservationOverlay
        :show="showReservationModal"
        :room="room"
        :organisations="userOrganisations"
        @close="showReservationModal = false"
    />
    <PublicAppLayout>
        <HeaderWave />

        <div class="w-full pb-12">
            <div
                class="grid grid-cols-1 items-stretch gap-12 border-b border-gray-100 lg:grid-cols-3"
            >
                <div class="lg:col-span-2">
                    <ContentCard position="start" class="h-full !rounded-none">
                        <div class="relative overflow-hidden">
                            <S3Image
                                class="aspect-video w-full bg-background object-cover"
                                :src="room.image_url"
                            />
                            <div
                                class="absolute bottom-6 left-0 z-10 rounded-r-2xl bg-haven-blue/90 px-8 py-3 shadow-lg"
                            >
                                <h1
                                    class="text-2xl font-extrabold text-haven-white lg:text-3xl"
                                >
                                    {{ room.name }}
                                </h1>
                            </div>
                        </div>

                        <div
                            class="mt-6 flex flex-col items-start justify-between gap-6 px-6 sm:flex-row"
                        >
                            <p
                                class="max-w-2xl text-lg leading-relaxed text-haven-black"
                            >
                                {{ room.description }}
                            </p>

                            <div class="shrink-0">
                                <div
                                    :class="[
                                        room.status.background_color,
                                        'rounded-full px-6 py-2 shadow-sm',
                                    ]"
                                >
                                    <span
                                        :class="room.status.text_color"
                                        class="text-sm font-bold tracking-widest uppercase"
                                    >
                                        {{ room.status.label }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <hr class="mx-6 my-8 border-t border-haven-blue/10" />

                        <div
                            v-if="page.props.auth.user"
                            class="flex flex-wrap justify-end gap-3 px-6 pb-6"
                        >
                            <button
                                @click="showReservationModal = true"
                                class="rounded-xl bg-haven-blue px-8 py-3 font-bold text-white transition-all hover:bg-haven-blue/90 active:scale-95"
                            >
                                Reserve Now
                            </button>
                            <Link :href="route('wip')">
                                <button
                                    class="rounded-xl border-2 border-haven-blue bg-white px-8 py-3 font-bold text-haven-blue transition-all hover:bg-haven-blue/5"
                                >
                                    Notify disturbance
                                </button>
                            </Link>
                        </div>
                    </ContentCard>
                </div>

                <div class="border-l border-gray-100 lg:col-span-1">
                    <ContentCard
                        position="end"
                        title="Reservation policy"
                        class="h-full"
                    >
                        <div class="px-2">
                            <ReservationPolicyShow
                                v-if="page.props.auth.user"
                                :policy="weeklyPolicies"
                            />
                            <div
                                v-else
                                class="flex h-48 flex-col items-center justify-center text-center"
                            >
                                <p class="px-4 font-medium text-gray-500">
                                    Log in to view your booking policies.
                                </p>
                            </div>
                        </div>
                    </ContentCard>
                </div>
            </div>

            <div class="mt-8 w-full">
                <ContentCard
                    position="full"
                    title="Upcoming Reservations"
                    class="!rounded-none"
                >
                    <div v-if="reservations.length > 0" class="px-6">
                        <div
                            class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5"
                        >
                            <div
                                v-for="reservation in reservations"
                                :key="reservation.id"
                                class="group flex flex-col rounded-2xl border border-gray-100 bg-haven-white/80 p-4 shadow-sm transition-shadow hover:shadow-md"
                            >
                                <div class="flex h-full flex-col">
                                    <h4
                                        class="mb-2 line-clamp-1 text-xl font-bold text-haven-black transition-colors group-hover:text-haven-blue"
                                    >
                                        {{ reservation.name }}
                                    </h4>

                                    <div
                                        class="mt-auto space-y-3 border-t border-gray-50 pt-4"
                                    >
                                        <div
                                            class="flex justify-between text-sm"
                                        >
                                            <span class="text-gray-500"
                                                >Date</span
                                            >
                                            <span class="font-semibold text-haven-black">{{
                                                formatDate(reservation.start_at)
                                            }}</span>
                                        </div>
                                        <div
                                            class="flex justify-between text-sm"
                                        >
                                            <span class="text-gray-500"
                                                >Time</span
                                            >
                                            <span
                                                class="font-semibold text-haven-blue"
                                            >
                                                {{
                                                    formatTime(
                                                        reservation.start_at,
                                                    )
                                                }}
                                                -
                                                {{
                                                    formatTime(
                                                        reservation.end_at,
                                                    )
                                                }}
                                            </span>
                                        </div>
                                        <div
                                            class="flex items-center justify-between text-sm"
                                        >
                                            <span class="text-gray-500"
                                                >User</span
                                            >
                                            <div
                                                v-if="reservation.organisation"
                                                class="flex items-center gap-2"
                                            >
                                                <span
                                                    class="max-w-[80px] truncate text-right font-medium text-haven-black"
                                                    >{{
                                                        reservation.organisation
                                                            .name
                                                    }}</span
                                                >
                                                <S3Image
                                                    :src="
                                                        reservation.organisation
                                                            .image_url
                                                    "
                                                    class="h-6 w-6 rounded-full border border-gray-200"
                                                />
                                            </div>
                                            <span v-else class="font-medium text-haven-black">{{
                                                reservation.user_name ||
                                                'Anonymous'
                                            }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="py-16 text-center">
                        <p class="text-gray-500 italic">
                            No reservations found for this room.
                        </p>
                    </div>
                </ContentCard>
            </div>
        </div>
    </PublicAppLayout>
</template>
