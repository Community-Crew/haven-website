<script setup lang="ts">
import { Organisation, Reservation, Room } from '@/types';
import { router, useForm } from '@inertiajs/vue3';
import { computed, getCurrentInstance, nextTick, ref, watch } from 'vue';

const app = getCurrentInstance();
const route = app?.appContext.config.globalProperties.route;

const props = withDefaults(
    defineProps<{
        show: boolean;
        room: Room;
        edit?: boolean;
        reservation?: Reservation;
        organisations?: Organisation[];
    }>(),
    {
        edit: false,
    },
);

const emit = defineEmits(['close']);

const bookingDate = ref('');
const startTimeStr = ref('');
const endTimeStr = ref('');

const form = useForm({
    name: '',
    start_time: '',
    end_time: '',
    share_name: true,
    room_id: props.room.id,
    organisation: (props.reservation && props.reservation.organisation) ? props.reservation.organisation.id : null,
});

// Generate 30-minute time slots (08:00 to 24:00)
const timeSlots = computed(() => {
    const slots = [];
    for (let i = 8; i < 24; i++) {
        const hour = i.toString().padStart(2, '0');
        slots.push(`${hour}:00`);
        slots.push(`${hour}:30`);
    }
    slots.push('24:00');
    return slots;
});

const endTimeOptions = computed(() => {
    if (!startTimeStr.value) return [];
    const startIndex = timeSlots.value.indexOf(startTimeStr.value);
    return timeSlots.value.slice(startIndex + 1);
});

watch([bookingDate, startTimeStr, endTimeStr], () => {
    if (bookingDate.value && startTimeStr.value) {
        form.start_time = `${bookingDate.value}T${startTimeStr.value}`;
    }
    if (bookingDate.value && endTimeStr.value) {
        form.end_time = `${bookingDate.value}T${endTimeStr.value}`;
    }
});

const extractTime = (dateStr: string) => {
    if (!dateStr) return '';
    const timePart = new Date(dateStr).toLocaleString([], {
        hour: '2-digit',
        minute: '2-digit',
    });
    if (!timePart) return '';

    const time = timePart.substring(0, 5);

    if (time === '00:00') return '24:00';

    return time;
};

// Helper: extract YYYY-MM-DD
const extractDate = (dateStr: string) => {
    if (!dateStr) return '';
    return dateStr.split(/[T ]/)[0];
};

watch(
    () => props.show,
    async (isOpen) => {
        if (isOpen) {
            if (props.edit && props.reservation) {
                // 1. Populate standard fields
                form.name = props.reservation.name;
                form.share_name = !!props.reservation.share_user;
                form.room_id = props.room.id;

                // 2. Parse raw strings directly
                const rawStart = props.reservation.start_at;
                const rawEnd = props.reservation.end_at;

                bookingDate.value = extractDate(rawStart);

                // 3. Set times
                const sTime = extractTime(rawStart);
                const eTime = extractTime(rawEnd);

                await nextTick();

                if (timeSlots.value.includes(sTime)) {
                    startTimeStr.value = sTime;
                }
                await nextTick();

                endTimeStr.value = eTime;
            } else {
                form.reset();
                form.clearErrors();
                bookingDate.value = new Date().toISOString().split('T')[0];
                startTimeStr.value = '';
                endTimeStr.value = '';
                form.room_id = props.room.id;
            }
        } else {
            form.reset();
            form.clearErrors();
            bookingDate.value = '';
            startTimeStr.value = '';
            endTimeStr.value = '';
        }
    },
    { immediate: true },
);

const submit = () => {
    if (props.edit && props.reservation) {
        form.put(route('reservations.update', props.reservation.id), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                emit('close');
            },
        });
    } else {
        form.post(route('reservations.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                emit('close');
            },
        });
    }
};

const cancelReservation = () => {
    if (!props.reservation) return;
    router.delete(route('reservations.destroy', props.reservation.id), {
        preserveScroll: true,
        onSuccess: () => emit('close'),
    });
};
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto px-4 py-6 sm:px-0"
            >
                <div
                    class="fixed inset-0 transform transition-all"
                    @click="$emit('close')"
                >
                    <div class="absolute inset-0 bg-gray-900 opacity-75"></div>
                </div>

                <div
                    class="z-50 mb-6 w-full max-w-lg transform overflow-hidden rounded-xl bg-haven-blue shadow-2xl transition-all sm:mx-auto"
                >
                    <div
                        class="flex items-center justify-between border-b bg-haven-blue px-6 py-4"
                    >
                        <h3 class="text-lg font-bold text-haven-yellow">
                            {{
                                edit ? 'Edit Reservation' : `Book ${room.name}`
                            }}
                        </h3>
                        <button
                            @click="$emit('close')"
                            class="text-gray-400 hover:text-gray-600 focus:outline-none"
                        >
                            <svg
                                class="h-6 w-6"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                ></path>
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="submit">
                        <div class="space-y-4 px-6 py-6">
                            <!-- Event Name -->
                            <div>
                                <label
                                    class="mb-1 block text-sm font-bold text-haven-yellow"
                                    >Event Name</label
                                >
                                <input
                                    v-model="form.name"
                                    type="text"
                                    placeholder="e.g. Team Sync"
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    :class="{
                                        'border-red-500': form.errors.name,
                                    }"
                                />
                                <p
                                    v-if="form.errors.name"
                                    class="mt-1 text-sm text-red-500"
                                >
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <!-- Date Picker -->
                            <div>
                                <label
                                    class="mb-1 block text-sm font-bold text-haven-yellow"
                                    >Date</label
                                >
                                <input
                                    v-model="bookingDate"
                                    type="date"
                                    :min="
                                        new Date().toISOString().split('T')[0]
                                    "
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>

                            <!-- Time Slots -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="mb-1 block text-sm font-bold text-haven-yellow"
                                        >Start Time</label
                                    >
                                    <select
                                        v-model="startTimeStr"
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        :disabled="!bookingDate"
                                    >
                                        <option value="" disabled>
                                            Select Start
                                        </option>
                                        <option
                                            v-for="time in timeSlots"
                                            :key="time"
                                            :value="time"
                                        >
                                            {{ time }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label
                                        class="mb-1 block text-sm font-bold text-haven-yellow"
                                        >End Time</label
                                    >
                                    <select
                                        v-model="endTimeStr"
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        :disabled="!startTimeStr"
                                    >
                                        <option value="" disabled>
                                            Select End
                                        </option>
                                        <option
                                            v-for="time in endTimeOptions"
                                            :key="time"
                                            :value="time"
                                        >
                                            {{ time }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Backend Error Messages -->
                            <div
                                v-if="form.errors.start_time"
                                class="text-sm text-red-500"
                            >
                                {{ form.errors.start_time }}
                            </div>
                            <div
                                v-if="form.errors.end_time"
                                class="text-sm text-red-500"
                            >
                                {{ form.errors.end_time }}
                            </div>

                            <!-- Privacy Toggle -->
                            <div
                                class="flex items-start rounded-md bg-haven-light-blue p-3"
                            >
                                <div class="flex h-5 items-center">
                                    <input
                                        v-model="form.share_name"
                                        id="share_name"
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />
                                </div>
                                <div class="ml-3 text-sm">
                                    <label
                                        for="share_name"
                                        class="font-medium text-gray-700"
                                        >Show name publicly</label
                                    >
                                    <p class="text-xs text-gray-500">
                                        Uncheck to show as "Anonymous" to other
                                        residents.
                                    </p>
                                </div>
                            </div>
                            <div v-if="organisations && organisations.length > 0">
                                <div>
                                    <label
                                        class="mb-1 block text-sm font-bold text-haven-yellow"
                                        >Organisation</label
                                    >
                                    <select
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        v-model="form.organisation"
                                    >
                                        <option :value="null" selected="true">
                                            none
                                        </option>
                                        <option
                                            v-for="organisation in organisations"
                                            v-bind:key="organisation.id"
                                            :value="organisation.id"
                                        >
                                            {{ organisation.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div
                            class="flex items-center justify-between border-t bg-haven-green px-6 py-4"
                        >
                            <!-- Left: Cancel Button (Only in Edit Mode) -->
                            <div>
                                <button
                                    v-if="edit"
                                    type="button"
                                    @click="cancelReservation"
                                    class="text-sm font-medium text-red-600 hover:text-red-800 focus:outline-none"
                                >
                                    Cancel Reservation
                                </button>
                            </div>

                            <!-- Right: Submit Button -->
                            <div class="flex gap-3">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="rounded-lg bg-haven-blue px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-blue-700 disabled:opacity-50"
                                >
                                    <span v-if="form.processing"
                                        >Processing...</span
                                    >
                                    <span v-else>{{
                                        edit
                                            ? 'Update Booking'
                                            : 'Confirm Booking'
                                    }}</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
