<script setup lang="ts">
import ContentCard from '@/components/ContentCard.vue';
import PublicAppLayout from '@/layouts/PublicAppLayout.vue';
import Pagination from '@/components/Pagination/Pagination.vue';
import ReservationOverlay from '@/components/Reservations/ReservationOverlay.vue';
import { Paginator, Reservation, Unit, Room, Organisation } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const created_at = new Date(user.value.created_at).toISOString().split('T')[0];

const props = defineProps<{
    unit: Unit | null;
    organisations: Organisation[];
    groups: string;
    reservations: Paginator<Reservation>;
}>();

// --- Modal State ---
const showingModal = ref(false);
const selectedReservation = ref<Reservation | undefined>(undefined);
const selectedRoom = ref<Room | undefined>(undefined);

const openEditModal = (reservation: Reservation) => {
    selectedReservation.value = reservation;
    selectedRoom.value = reservation.room;
    showingModal.value = true;
};

const closeModal = () => {
    showingModal.value = false;
    setTimeout(() => {
        selectedReservation.value = undefined;
        selectedRoom.value = undefined;
    }, 200);
};

const isEditable = (reservation: Reservation) => {
    const start = new Date(reservation.start_at);
    const now = new Date();
    return start > now && reservation.status.label !== 'Canceled' && reservation.status.label !== "Rejected";
};

// --- Carousel Logic ---
type CarouselItem =
    | { type: 'unit'; data: Unit }
    | { type: 'org'; data: Organisation };

const currentIndex = ref(0);
const autoRotateInterval = ref<number | null>(null);

const carouselItems = computed<CarouselItem[]>(() => {
    const items: CarouselItem[] = [];

    if (props.unit) {
        items.push({ type: 'unit', data: props.unit });
    }

    if (props.organisations && props.organisations.length > 0) {
        props.organisations.forEach(org => {
            items.push({ type: 'org', data: org });
        });
    }

    return items;
});

const currentItem = computed(() => {
    if (carouselItems.value.length === 0) return null;
    return carouselItems.value[currentIndex.value];
});

const cardTitle = computed(() => {
    if (!currentItem.value) return 'Info';
    return currentItem.value.type === 'unit' ? 'Unit' : 'Organisation';
});

const nextSlide = () => {
    if (carouselItems.value.length <= 1) return;
    currentIndex.value = (currentIndex.value + 1) % carouselItems.value.length;
};

const prevSlide = () => {
    if (carouselItems.value.length <= 1) return;
    currentIndex.value =
        currentIndex.value === 0
            ? carouselItems.value.length - 1
            : currentIndex.value - 1;
};

const startRotation = () => {
    if (carouselItems.value.length > 1) {
        // @ts-ignore
        autoRotateInterval.value = setInterval(nextSlide, 6000);
    }
};

const stopRotation = () => {
    if (autoRotateInterval.value) {
        clearInterval(autoRotateInterval.value);
        autoRotateInterval.value = null;
    }
};

onMounted(() => startRotation());
onUnmounted(() => stopRotation());
</script>

<template>
    <PublicAppLayout>
        <div class="h-10" />
        <div class="mx-auto">
            <div class="mb-12 grid grid-cols-1 gap-6 md:grid-cols-2 md:gap-8">

                <!-- LEFT COLUMN: Profile (Visually Untouched) -->
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

                <div v-if="currentItem">
                    <!-- class="h-full" ensures it matches Profile card height via Grid -->
                    <ContentCard
                        :title="cardTitle"
                        position="end"
                        @mouseenter="stopRotation"
                        @mouseleave="startRotation"
                        class="h-full"
                    >
                        <!--
                            Relative container for buttons.
                            h-full ensures the content fills the card even if short.
                        -->
                        <div class="relative flex h-full flex-col justify-between">

                            <!-- Left Arrow: Centered Absolute -->
                            <button
                                v-if="carouselItems.length > 1"
                                @click="prevSlide"
                                class="absolute left-[-10px] md:left-0 top-1/2 z-20 -translate-y-1/2 rounded-full p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-haven-blue focus:outline-none"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-5 w-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                                </svg>
                            </button>

                            <!-- Carousel Content Wrapper -->
                            <!-- Added px-6 to prevent text overlap with arrows -->
                            <div class="w-full px-6 flex-grow">
                                <Transition name="fade" mode="out-in">

                                    <!-- SCENARIO 1: UNIT -->
                                    <div
                                        v-if="currentItem.type === 'unit'"
                                        :key="'unit'"
                                        class="flex h-full flex-col"
                                    >
                                        <!-- Header Wrapper: Fixed Min-Height to align HR with Profile Card -->
                                        <!-- 5.5rem roughly equals h2(2rem) + mt-4(1rem) + p(1.5rem) + spacing -->
                                        <div class="flex min-h-[5.5rem] flex-col justify-end text-haven-black pb-1">
                                            <h2 class="text-2xl font-bold leading-tight">{{ currentItem.data.name }}</h2>
                                        </div>

                                        <hr class="my-6 border-t border-haven-blue/20" />

                                        <div class="space-y-4">
                                            <div class="flex items-center justify-between">
                                                <span class="font-semibold">Building:</span>
                                                <span class="rounded-full bg-green-200 px-2.5 py-0.5 text-sm font-medium text-green-800">
                                                    {{ currentItem.data.building }}
                                                </span>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span class="font-semibold">Floor:</span>
                                                <span class="text-brand-dark-blue/80">{{
                                                        currentItem.data.floor.toString().padStart(2, '0')
                                                    }}</span>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span class="font-semibold">Unit:</span>
                                                <span class="text-brand-dark-blue/80">{{
                                                        currentItem.data.unit.padStart(2, '0')
                                                    }}</span>
                                            </div>
                                            <div v-if="currentItem.data.subunit" class="flex items-center justify-between">
                                                <span class="font-semibold">Room:</span>
                                                <span class="text-brand-dark-blue/80">{{ currentItem.data.subunit }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- SCENARIO 2: ORGANISATION -->
                                    <div
                                        v-else-if="currentItem.type === 'org'"
                                        :key="'org-' + currentItem.data.id"
                                        class="flex h-full flex-col"
                                    >
                                        <!-- Header Wrapper: Same Min-Height for Alignment -->
                                        <div class="flex min-h-[5.5rem] items-end justify-between gap-4 text-haven-black pb-1">
                                            <!-- Name (Left) -->
                                            <h2 class="text-2xl font-bold leading-tight mb-0.5">
                                                {{ currentItem.data.name }}
                                            </h2>

                                            <!-- Logo (Right) -->
                                            <div class="flex-shrink-0 mb-1">
                                                <img
                                                    v-if="currentItem.data.logo"
                                                    :src="currentItem.data.logo"
                                                    :alt="currentItem.data.name"
                                                    class="h-14 w-14 object-contain"
                                                />
                                                <div
                                                    v-else
                                                    class="flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-xl font-bold text-gray-400"
                                                >
                                                    {{ currentItem.data.name.charAt(0) }}
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="my-6 border-t border-haven-blue/20" />

                                        <div class="space-y-4">
                                            <p class="whitespace-pre-line text-brand-dark-blue/80">
                                                {{ currentItem.data.about || 'No description available.' }}
                                            </p>
                                        </div>
                                    </div>

                                </Transition>
                            </div>

                            <!-- Right Arrow: Centered Absolute -->
                            <button
                                v-if="carouselItems.length > 1"
                                @click="nextSlide"
                                class="absolute right-[-10px] md:right-0 top-1/2 z-20 -translate-y-1/2 rounded-full p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-haven-blue focus:outline-none"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-5 w-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                </svg>
                            </button>

                            <!-- Dots Indicator (Pushed to bottom) -->
                            <div v-if="carouselItems.length > 1" class="mt-auto pt-6 flex justify-center gap-2">
                                <button
                                    v-for="(item, index) in carouselItems"
                                    :key="index"
                                    @click="currentIndex = index"
                                    class="h-2 rounded-full transition-all duration-300"
                                    :class="index === currentIndex ? 'w-6 bg-haven-blue' : 'w-2 bg-gray-300 hover:bg-gray-400'"
                                    :aria-label="'Go to slide ' + (index + 1)"
                                ></button>
                            </div>
                        </div>
                    </ContentCard>
                </div>

                <!-- BOTTOM: Reservations (Unchanged) -->
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

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
