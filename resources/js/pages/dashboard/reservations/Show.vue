<script setup lang="ts">
import ContentCard from '@/components/ContentCard.vue';
import AdminDashboardLayout from '@/layouts/AdminDashboardLayout.vue';
import { Reservation, Room } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { getCurrentInstance, ref } from 'vue';

const app = getCurrentInstance();
const route = app?.appContext.config.globalProperties.route;

const props = defineProps<{
    reservation: Reservation;
    rooms: Room[];
    statuses: string[];
}>();

const isEditing = ref(false);

// 1. Helper to format for <input type="datetime-local"> (YYYY-MM-DDTHH:mm)
const formatDateForInput = (dateString: string) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    const local = new Date(date.getTime() - date.getTimezoneOffset() * 60000);
    return local.toISOString().slice(0, 16);
};

// 2. Helper to format for Display (e.g. Jan 10, 2023, 10:00 AM)
const formatDateForDisplay = (dateString: string) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleString('en-US', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
};

const form = useForm({
    name: props.reservation.name,
    email: props.reservation.user.email,
    room: props.reservation.room.id,
    status: props.reservation.status.name.toLowerCase(),
    start_time: formatDateForInput(props.reservation.start_at),
    end_time: formatDateForInput(props.reservation.end_at),
});

const enableEdit = () => {
    isEditing.value = true;
};

const cancelEdit = () => {
    form.reset(); // Revert changes
    form.clearErrors();
    isEditing.value = false;
};

const submit = () => {
    form.put(route('admin.reservations.update', props.reservation.id), {
        onSuccess: () => {
            isEditing.value = false;
        },
    });
};
</script>

<template>
    <AdminDashboardLayout>
        <div class="mt-8 grid grid-cols-1 gap-4 md:grid-cols-2">
            <ContentCard
                position="start"
                :title="
                    'Reservation #' + reservation.id.toString().padStart(6, '0')
                "
            >
                <!-- Header / Actions -->
                <div
                    class="mb-6 flex items-center justify-between border-b pb-4"
                >
                    <span
                        v-if="!isEditing"
                        class="text-lm rounded-full px-2.5 py-0.5 font-semibold capitalize"
                        :class="
                            reservation.status.text_color +
                            ' ' +
                            reservation.status.background_color
                        "
                    >
                        {{ reservation.status.label }}
                    </span>
                    <button
                        v-if="!isEditing"
                        @click="enableEdit"
                        class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
                    >
                        Edit Reservation
                    </button>
                </div>

                <!-- READ ONLY VIEW -->
                <div v-if="!isEditing" class="space-y-4">
                    <div>
                        <p class="text-sm font-bold text-gray-500">
                            Reservation Name
                        </p>
                        <p class="text-lg text-gray-900">
                            {{ reservation.name }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm font-bold text-gray-500">
                            Email Address
                        </p>
                        <p class="text-gray-900">
                            {{ reservation.user.email }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm font-bold text-gray-500">Room</p>
                        <p class="text-gray-900">{{ reservation.room.name }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-bold text-gray-500">
                                Start Time
                            </p>
                            <p class="text-gray-900">
                                {{ formatDateForDisplay(reservation.start_at) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-500">
                                End Time
                            </p>
                            <p class="text-gray-900">
                                {{ formatDateForDisplay(reservation.end_at) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- EDIT FORM -->
                <form v-else @submit.prevent="submit" class="flex flex-col">
                    <div class="mb-4">
                        <label
                            class="mb-2 block text-sm font-bold text-gray-700"
                            for="status"
                        >
                            Status
                        </label>
                        <select
                            v-model="form.status"
                            id="status"
                            class="w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        >
                            <option v-for="status in statuses" :key="status" :value="status">{{ status }}</option>
                        </select>
                        <div
                            v-if="form.errors.status"
                            class="mt-1 text-sm text-red-500"
                        >
                            {{ form.errors.status }}
                        </div>
                    </div>
                    <div class="mb-4">
                        <label
                            class="mb-2 block text-sm font-bold text-gray-700"
                            for="name"
                        >
                            Reservation Name
                        </label>
                        <input
                            v-model="form.name"
                            id="name"
                            type="text"
                            class="w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            :class="{ 'border-red-500': form.errors.name }"
                        />
                        <div
                            v-if="form.errors.name"
                            class="mt-1 text-sm text-red-500"
                        >
                            {{ form.errors.name }}
                        </div>
                    </div>

                    <div class="mb-4">
                        <label
                            class="mb-2 block text-sm font-bold text-gray-700"
                            for="email"
                        >
                            Email Address
                        </label>
                        <input
                            v-model="form.email"
                            id="email"
                            type="email"
                            class="w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            :class="{ 'border-red-500': form.errors.email }"
                        />
                        <div
                            v-if="form.errors.email"
                            class="mt-1 text-sm text-red-500"
                        >
                            {{ form.errors.email }}
                        </div>
                    </div>

                    <div class="mb-4">
                        <label
                            class="mb-2 block text-sm font-bold text-gray-700"
                            for="room"
                        >
                            Select Room
                        </label>
                        <select
                            v-model="form.room"
                            id="room"
                            class="w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            :class="{ 'border-red-500': form.errors.room }"
                        >
                            <option value="" disabled>Choose a room</option>
                            <option
                                v-for="room in props.rooms"
                                :key="room.id"
                                :value="room.id"
                            >
                                {{ room.name }}
                            </option>
                        </select>
                        <div
                            v-if="form.errors.room"
                            class="mt-1 text-sm text-red-500"
                        >
                            {{ form.errors.room }}
                        </div>
                    </div>

                    <div class="mb-6 grid grid-cols-2 gap-4">
                        <div>
                            <label
                                class="mb-2 block text-sm font-bold text-gray-700"
                                for="start_time"
                            >
                                Start Time
                            </label>
                            <input
                                v-model="form.start_time"
                                id="start_time"
                                type="datetime-local"
                                class="w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="{
                                    'border-red-500': form.errors.start_time,
                                }"
                            />
                            <div
                                v-if="form.errors.start_time"
                                class="mt-1 text-sm text-red-500"
                            >
                                {{ form.errors.start_time }}
                            </div>
                        </div>
                        <div>
                            <label
                                class="mb-2 block text-sm font-bold text-gray-700"
                                for="end_time"
                            >
                                End Time
                            </label>
                            <input
                                v-model="form.end_time"
                                id="end_time"
                                type="datetime-local"
                                class="w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="{
                                    'border-red-500': form.errors.end_time,
                                }"
                            />
                            <div
                                v-if="form.errors.end_time"
                                class="mt-1 text-sm text-red-500"
                            >
                                {{ form.errors.end_time }}
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            @click="cancelEdit"
                            class="flex-1 rounded-lg border border-gray-300 bg-white px-4 py-2 font-bold text-gray-700 transition duration-300 hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex-1 rounded-lg bg-blue-600 px-4 py-2 font-bold text-white transition duration-300 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <span v-if="form.processing">Saving...</span>
                            <span v-else>Save Changes</span>
                        </button>
                    </div>
                </form>
            </ContentCard>
        </div>
    </AdminDashboardLayout>
</template>
