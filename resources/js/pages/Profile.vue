<script setup lang="ts">
import ContentCard from '@/components/ContentCard.vue';
import PublicAppLayout from '@/layouts/PublicAppLayout.vue';
import Pagination from '@/components/Pagination/Pagination.vue';

import { Paginator, Reservation, Unit, Room } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ReservationOverlay from '@/components/Reservations/ReservationOverlay.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const created_at = new Date(user.value.created_at).toISOString().split('T')[0];

const props = defineProps<{
    unit: Unit;
    groups: string;
    reservations: Paginator<Reservation>;
}>();

// --- Modal State Management ---
const showingModal = ref(false);
const selectedReservation = ref<Reservation | undefined>(undefined);
const selectedRoom = ref<Room | undefined>(undefined);

const openEditModal = (reservation: Reservation) => {
    selectedReservation.value = reservation;
    // Assuming the reservation object has the room relationship loaded
    selectedRoom.value = reservation.room;
    showingModal.value = true;
};

const closeModal = () => {
    showingModal.value = false;
    setTimeout(() => {
        selectedReservation.value = undefined;
        selectedRoom.value = undefined;
    }, 200); // Small delay to clear data after transition
};

// Helper to check if reservation is in the future
const isEditable = (reservation: Reservation) => {
    const start = new Date(reservation.start_at);
    const now = new Date();
    return start > now && reservation.status.label !== 'Canceled';
};
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
                            <span class="rounded-full bg-green-200 px-2.5 py-0.5 text-sm font-medium text-green-800">
                                {{ 'Validated' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="font-semibold">Member Since:</span>
                            <span class="text-brand-dark-blue/80">{{ created_at }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="font-semibold">Groups:</span>
                            <span class="text-brand-dark-blue/80">{{ groups }}</span>
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
                            <span class="rounded-full bg-green-200 px-2.5 py-0.5 text-sm font-medium text-green-800">
                                {{ props.unit.building }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="font-semibold">Floor:</span>
                            <span class="text-brand-dark-blue/80">{{ props.unit.floor.toString().padStart(2, '0') }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="font-semibold">Unit:</span>
                            <span class="text-brand-dark-blue/80">{{ props.unit.unit.padStart(2, '0') }}</span>
                        </div>
                        <div v-if="props.unit.subunit" class="flex items-center justify-between">
                            <span class="font-semibold">Room:</span>
                            <span class="text-brand-dark-blue/80">{{ props.unit.subunit }}</span>
                        </div>
                    </div>
                </ContentCard>

                <ContentCard
                    title="Upcoming reservations"
                    position="full"
                    class="md:col-span-2"
                >
                    <div v-if="reservations.data.length > 0" class="mt-4 grid gap-x-8 gap-y-4 md:grid-cols-2">
                        <div
                            v-for="reservation in reservations.data"
                            :key="reservation.id"
                            class="relative flex w-full flex-col justify-between rounded-2xl bg-haven-white/45 p-4 shadow transition-shadow hover:shadow-md"
                        >
                            <div class="mb-2 flex items-start justify-between">
                                <div class="overflow-hidden">
                                    <p class="truncate font-bold text-haven-black" :title="reservation.name">
                                        {{ reservation.name }}
                                    </p>
                                    <p class="text-sm text-haven-blue/80">
                                        {{ reservation.room.name }}
                                    </p>
                                </div>
                                <div class="flex flex-col items-end gap-2">
                                    <span
                                        :class="reservation.status.background_color + ' ' + reservation.status.text_color"
                                        class="rounded-lg px-2 py-1 text-xs font-bold uppercase tracking-wide"
                                    >
                                        {{ reservation.status.label }}
                                    </span>
                                </div>
                            </div>

                            <hr class="my-2 border-t border-haven-blue/20" />

                            <div class="flex items-end justify-between">
                                <div class="text-sm text-gray-500">
                                    <div class="flex items-center gap-1">
                                        <span class="font-medium text-gray-700">From:</span>
                                        {{ new Date(reservation.start_at).toLocaleString([], {
                                        month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
                                    }) }}
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <span class="font-medium text-gray-700">To:</span>
                                        <span class="ml-4">
                                            {{ new Date(reservation.end_at).toLocaleString([], {
                                            month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
                                        }) }}
                                        </span>
                                    </div>
                                </div>

                                <button
                                    v-if="isEditable(reservation)"
                                    @click="openEditModal(reservation)"
                                    class="group flex items-center gap-1 rounded-lg bg-white px-3 py-1.5 text-xs font-bold text-haven-blue shadow-sm ring-1 ring-inset ring-gray-300 transition-colors hover:bg-gray-50"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 text-gray-400 group-hover:text-haven-blue">
                                        <path d="M5.433 13.917l1.262-3.155A4 4 0 017.58 9.42l6.92-6.918a2.121 2.121 0 013 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 01-.65-.65z" />
                                        <path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0010 3H4.75A2.75 2.75 0 002 5.75v9.5A2.75 2.75 0 004.75 18h9.5A2.75 2.75 0 0017 15.25V10a.75.75 0 00-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5z" />
                                    </svg>
                                    Edit
                                </button>
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

        <ReservationOverlay
            v-if="showingModal && selectedRoom && selectedReservation"
            :show="showingModal"
            :room="selectedRoom"
            :reservation="selectedReservation"
            :edit="true"
            @close="closeModal"
        />

    </PublicAppLayout>
</template>

<style scoped></style>
