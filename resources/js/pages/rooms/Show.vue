<script setup lang="ts">
import ContentCard from '@/components/ContentCard.vue';
import HeaderWave from '@/components/HeaderWave.vue';
import ReservationOverlay from '@/components/Reservations/ReservationOverlay.vue';
import ReservationPolicyShow from '@/components/Reservations/ReservationPolicyShow.vue';
import S3Image from '@/components/S3Image.vue';
import PublicAppLayout from '@/layouts/PublicAppLayout.vue';
import { Organisation, Reservation, Room } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const page = usePage();

defineProps<{
    room: Room;
    reservations: Reservation[];
    policy: any[][];
    maxDaysInAdvance: number[];
    userOrganisations: Organisation[];
}>();

const showReservationModal = ref(false);

const getDateString = (date: Date) => {
    return date.toLocaleDateString('nl-NL', {});
};
</script>

<template>
    <PublicAppLayout>
        <HeaderWave />
        <div class="relative flex flex-col md:flex-row">
            <div class="w-full md:w-1/2 md:pr-4">
                <ContentCard position="start">
                    <div class="relative pb-2">
                        <S3Image
                            class="aspect-video rounded-2xl bg-background"
                            :src="room.image_url"
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
                    <div
                        class="flex items-center justify-between gap-4 px-2 pt-2"
                    >
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
                    <hr class="my-6 border-t border-haven-blue/20" />
                    <div
                        v-if="page.props.auth.user"
                        class="flex w-full content-center justify-end gap-4"
                    >
                        <button @click="showReservationModal = true">
                            <span class="rounded-2xl bg-haven-blue p-2">
                                Reserve
                            </span>
                        </button>
                        <Link :href="route('wip')">
                            <button>
                                <span class="rounded-2xl bg-haven-blue p-2"
                                    >Notify disturbance</span
                                >
                            </button>
                        </Link>
                    </div>
                </ContentCard>
            </div>
            <div
                class="hidden w-full md:absolute md:inset-y-0 md:right-0 md:block md:w-1/2 md:pl-4"
            >
                <ContentCard
                    position="end"
                    title="Reservation policy"
                    class="h-full"
                >
                    <ReservationPolicyShow
                        v-if="page.props.auth.user"
                        :policy="policy"
                        :maxDaysInAdvance="maxDaysInAdvance"
                    />
                    <div v-else class="text-center text-gray-600">
                        You need to be logged in to see your policy.
                    </div>
                </ContentCard>
            </div>
        </div>

        <div class="pt-8">
            <ContentCard position="full" title="Reservations">
                <div v-if="reservations.length > 0">
                    <div
                        class="grid grid-cols-1 items-stretch gap-4 md:grid-cols-3 lg:grid-cols-5"
                    >
                        <div
                            v-for="reservation in reservations"
                            :key="reservation.id"
                            class="flex flex-col rounded-2xl bg-white/30 p-2 shadow"
                        >
                            <div class="mx-2 h-40 text-haven-black">
                                <p
                                    class="line-clamp-2 h-16 overflow-hidden text-2xl leading-8 font-medium break-all"
                                >
                                    {{ reservation.name }}
                                </p>
                                <div>
                                    <hr
                                        class="border-t-2 border-haven-blue/20"
                                    />
                                    <div
                                        class="flex flex-col rounded-b-2xl py-2"
                                    >
                                        <div class="grid w-full grid-cols-2">
                                            <p class="justify-self-start">
                                                Date:
                                            </p>
                                            <p
                                                class="justify-self-end font-medium"
                                            >
                                                {{
                                                    getDateString(
                                                        new Date(
                                                            reservation.start_at,
                                                        ),
                                                    )
                                                }}
                                            </p>
                                        </div>
                                        <div class="grid w-full grid-cols-2">
                                            <p class="justify-self-start">
                                                Time:
                                            </p>
                                            <p
                                                class="justify-self-end font-medium"
                                            >
                                                {{
                                                    new Date(
                                                        reservation.start_at,
                                                    ).toLocaleTimeString([], {
                                                        hour: '2-digit',
                                                        minute: '2-digit',
                                                    })
                                                }}
                                                -
                                                {{
                                                    new Date(
                                                        reservation.end_at,
                                                    ).toLocaleTimeString([], {
                                                        hour: '2-digit',
                                                        minute: '2-digit',
                                                    })
                                                }}
                                            </p>
                                        </div>
                                        <div class="grid w-full grid-cols-2">
                                            <p class="justify-self-start">
                                                Who:
                                            </p>
                                            <div
                                                v-if="
                                                    reservation.user_name &&
                                                    reservation.organisation
                                                "
                                                class="flex items-center justify-end font-medium"
                                            >
                                                <S3Image
                                                    :src="
                                                        reservation.organisation
                                                            .image_url
                                                    "
                                                    class="ml-2 inline"
                                                />
                                                <p class="inline">
                                                    {{
                                                        reservation.organisation
                                                            .name
                                                    }}
                                                </p>
                                            </div>
                                            <p
                                                v-else-if="
                                                    reservation.user_name
                                                "
                                                class="justify-self-end font-medium"
                                            >
                                                {{ reservation.user_name }}
                                            </p>
                                            <p
                                                v-else
                                                class="justify-self-end font-medium"
                                            >
                                                Anonymous
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="py-8 text-center text-gray-600">
                    No reservations found.
                    <p v-if="!page.props.auth.user">(Are you logged in?)</p>
                </div>
            </ContentCard>
        </div>
    </PublicAppLayout>
    <ReservationOverlay
        :show="showReservationModal"
        :room="room"
        :organisations="userOrganisations"
        @close="showReservationModal = false"
    />
</template>

<style scoped></style>
