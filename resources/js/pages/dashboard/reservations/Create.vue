<script setup lang="ts">
import ContentCard from '@/components/ContentCard.vue';
import HeaderWave from '@/components/HeaderWave.vue';
import AdminDashboardLayout from '@/layouts/AdminDashboardLayout.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, getCurrentInstance } from 'vue';
import { Room } from '@/types';

const app = getCurrentInstance();
interface ReservationForm {
    name: string;
    email: string;
    room: Room[] | string;
    start_time: string;
    end_time: string;
}

const page = usePage();
const successMessage = computed(() => page.props.flash.success);
const failMessage = computed(() => page.props.flash.error);

const form = useForm<ReservationForm>({
    name: '',
    email: page.props.auth.user.email || '',
    room: '',
    start_time: '',
    end_time: '',
});

const props = defineProps<{
    rooms: Room[];
}>();

const submit = () => {
    form.post('/admin/reservations', {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <AdminDashboardLayout>
        <HeaderWave />
        <ContentCard title="Create Reservation" class="m-6">
            <form @submit.prevent="submit" class="flex flex-col">
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
                    <p class="mt-1 text-xs text-gray-500">
                        Defaults to your email. Change this to book for someone
                        else.
                    </p>
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
                        <option v-for="room in props.rooms" :key="room.id" :value="room.id">
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

                <div class="mb-4 grid grid-cols-2">
                    <div class="mr-2">
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
                    <div class="ml-2">
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
                            :class="{ 'border-red-500': form.errors.end_time }"
                        />
                        <div
                            v-if="form.errors.end_time"
                            class="mt-1 text-sm text-red-500"
                        >
                            {{ form.errors.end_time }}
                        </div>
                    </div>
                </div>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full rounded-lg bg-blue-600 px-4 py-2 font-bold text-white transition duration-300 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <span v-if="form.processing">Saving...</span>
                    <span v-else>Create Reservation</span>
                </button>
            </form>
        </ContentCard>
    </AdminDashboardLayout>
</template>

<style scoped></style>
