<script setup lang="ts">
import { Room } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { getCurrentInstance, watch, ref, computed } from 'vue';

const app = getCurrentInstance();
const route = app?.appContext.config.globalProperties.route;

const props = defineProps<{
    show: boolean;
    room: Room;
}>();

const emit = defineEmits(['close']);

// 1. Local state for splitting date and time
const bookingDate = ref('');
const startTimeStr = ref('');
const endTimeStr = ref('');

const form = useForm({
    name: '',
    start_time: '', // These will be constructed from local state
    end_time: '',
    share_name: true,
    room: props.room.id,
});

// 2. Generate 30-minute time slots (00:00, 00:30, ... 23:30)
const timeSlots = computed(() => {
    const slots = [];
    for (let i = 8; i < 24; i++) {
        const hour = i.toString().padStart(2, '0');
        slots.push(`${hour}:00`);
        slots.push(`${hour}:30`);
    }
    slots.push('24:00')
    return slots;
});

// 3. Filter End Time options to ensure they are AFTER start time
const endTimeOptions = computed(() => {
    if (!startTimeStr.value) return [];

    // Get index of selected start time
    const startIndex = timeSlots.value.indexOf(startTimeStr.value);

    // Return all slots AFTER the start time
    // We stick to the "Same Day" rule, so we don't wrap to next day
    return timeSlots.value.slice(startIndex + 1);
});

// 4. Watch for changes in local state to update the actual Form object
watch([bookingDate, startTimeStr, endTimeStr], () => {
    if (bookingDate.value && startTimeStr.value) {
        form.start_time = `${bookingDate.value}T${startTimeStr.value}`;
    } else {
        form.start_time = '';
    }

    if (bookingDate.value && endTimeStr.value) {
        form.end_time = `${bookingDate.value}T${endTimeStr.value}`;
    } else {
        form.end_time = '';
    }
});

// Reset form when modal opens/closes
watch(
    () => props.show,
    (newVal) => {
        if (!newVal) {
            form.reset();
            form.clearErrors();
            // Reset local state
            bookingDate.value = '';
            startTimeStr.value = '';
            endTimeStr.value = '';
        } else {
            // Optional: Default to Today
            const today = new Date();
            bookingDate.value = today.toISOString().split('T')[0];
        }
    },
);

const submit = () => {
    form.post(route('reservations.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            emit('close');
        },
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
                            Book {{ room.name }}
                        </h3>
                        <button
                            @click="$emit('close')"
                            class="text-gray-400 hover:text-gray-600 focus:outline-none"
                        >
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="submit">
                        <div class="space-y-4 px-6 py-6">
                            <!-- Event Name -->
                            <div>
                                <label class="mb-1 block text-sm font-bold text-haven-yellow">Event Name</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    placeholder="e.g. Team Sync"
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    :class="{ 'border-red-500': form.errors.name }"
                                />
                                <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <!-- Single Date Picker (Enforces Same Day Rule) -->
                            <div>
                                <label class="mb-1 block text-sm font-bold text-haven-yellow">Date</label>
                                <input
                                    v-model="bookingDate"
                                    type="date"
                                    :min="new Date().toISOString().split('T')[0]"
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    :class="{ 'border-red-500': form.errors.start_time || form.errors.end_time }"
                                />
                            </div>

                            <!-- Time Slots (Enforces 30-min Rule) -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="mb-1 block text-sm font-bold text-haven-yellow">Start Time</label>
                                    <select
                                        v-model="startTimeStr"
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        :disabled="!bookingDate"
                                    >
                                        <option value="" disabled>Select Start</option>
                                        <option v-for="time in timeSlots" :key="time" :value="time">
                                            {{ time }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-bold text-haven-yellow">End Time</label>
                                    <select
                                        v-model="endTimeStr"
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        :disabled="!startTimeStr"
                                    >
                                        <option value="" disabled>Select End</option>
                                        <option v-for="time in endTimeOptions" :key="time" :value="time">
                                            {{ time }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Backend Error Messages -->
                            <div v-if="form.errors.start_time" class="text-sm text-red-500">
                                {{ form.errors.start_time }}
                            </div>
                            <div v-if="form.errors.end_time" class="text-sm text-red-500">
                                {{ form.errors.end_time }}
                            </div>

                            <!-- Privacy Toggle -->
                            <div class="flex items-start rounded-md bg-haven-light-blue p-3">
                                <div class="flex h-5 items-center">
                                    <input
                                        v-model="form.share_name"
                                        id="share_name"
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="share_name" class="font-medium text-gray-700">Show name publicly</label>
                                    <p class="text-xs text-gray-500">
                                        Uncheck to show as "Anonymous" to other residents.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="flex items-center justify-end gap-3 border-t bg-haven-green px-6 py-4">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="rounded-lg bg-haven-blue px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-blue-700 disabled:opacity-50"
                            >
                                <span v-if="form.processing">Checking...</span>
                                <span v-else>Confirm Booking</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
