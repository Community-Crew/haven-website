<script setup lang="ts">
import ContentCard from '@/components/ContentCard.vue';
import Pagination from '@/components/Pagination/Pagination.vue';
import S3Image from '@/components/S3Image.vue';
import AdminDashboardLayout from '@/layouts/AdminDashboardLayout.vue';
import {
    Paginator,
    Reservation,
    ReservationFilters,
    ReservationStatus,
    Room,
    RoomStatus,
} from '@/types';
import { Link, router, useForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, ref, watch } from 'vue';

interface RoomForm {
    _method: string;
    name: string;
    description: string;
    location: string;
    image: File | null;
    status: RoomStatus;
}

const props = defineProps<{
    room: Room;
    statusOptions: RoomStatus[];
    reservationStatusOptions: ReservationStatus[];
    reservations: Paginator<Reservation>;
    filters: ReservationFilters;
}>();

const form = useForm<RoomForm>({
    _method: 'PUT',
    name: props.room.name,
    description: props.room.description,
    location: props.room.location,
    image: null,
    status: props.room.status,
});

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        form.image = target.files[0];
    }
};

const fileName = computed(() => {
    if (form.image) {
        return form.image.name;
    }
    return props.room.image_url ? 'Current Image' : 'Untitled.jpg';
});

const submit = () => {
    form.post(`/admin/rooms/${props.room.slug}`, {
        onSuccess: () => form.reset('image'),
    });
};

const filterForm = ref({
    status: props.filters.status ?? null,
    date: props.filters.date ?? null,
});

watch(
    filterForm,
    debounce(() => {
        router.get(`/admin/rooms/${props.room.slug}`, filterForm.value, {
            preserveState: true,
            replace: true,
        });
    }, 300),
    { deep: true },
);

const reset = () => {
    filterForm.value = {
        status: null,
        date: null,
    };
};
</script>

<template>
    <AdminDashboardLayout>
        <div class="mt-8 grid w-full grid-cols-2 gap-8">
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
                <div class="flex items-center justify-between gap-4 px-2 pt-2">
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
            </ContentCard>
            <ContentCard position="end" title="Edit" class="overflow-hidden">
                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Name Input -->
                    <div class="group">
                        <label
                            class="mb-1 block text-xs font-bold tracking-wide text-gray-700 uppercase"
                            >Name</label
                        >
                        <input
                            type="text"
                            v-model="form.name"
                            class="w-full rounded-lg border-none bg-white/25 px-4 py-3 text-haven-black placeholder-white/70 shadow-sm focus:bg-white/30 focus:ring-2 focus:ring-white/50"
                            placeholder="Room Name"
                        />
                        <div
                            v-if="form.errors.name"
                            class="mt-1 text-xs font-bold text-red-600"
                        >
                            {{ form.errors.name }}
                        </div>
                    </div>

                    <!-- Description Input -->
                    <div class="group">
                        <label
                            class="mb-1 block text-xs font-bold tracking-wide text-gray-700 uppercase"
                            >Description</label
                        >
                        <textarea
                            v-model="form.description"
                            rows="3"
                            class="w-full resize-none rounded-lg border-none bg-white/25 px-4 py-3 text-haven-black placeholder-white/70 shadow-sm focus:bg-white/30 focus:ring-2 focus:ring-white/50"
                            placeholder="Description..."
                        ></textarea>
                    </div>

                    <!-- Location Input -->
                    <div class="group">
                        <label
                            class="mb-1 block text-xs font-bold tracking-wide text-gray-700 uppercase"
                            >Location</label
                        >
                        <input
                            type="text"
                            v-model="form.location"
                            class="w-full rounded-lg border-none bg-white/25 px-4 py-3 text-haven-black placeholder-white/70 shadow-sm focus:bg-white/30 focus:ring-2 focus:ring-white/50"
                        />
                    </div>

                    <!-- Status Select -->
                    <div class="group">
                        <label
                            class="mb-1 block text-xs font-bold tracking-wide text-gray-700 uppercase"
                            >Status</label
                        >
                        <div class="relative">
                            <select
                                v-model="form.status"
                                class="w-full appearance-none rounded-lg border-none bg-white/25 px-4 py-3 text-haven-black shadow-sm focus:bg-white/30 focus:ring-2 focus:ring-white/50"
                            >
                                <option
                                    v-for="option in statusOptions"
                                    :key="option.value"
                                    :value="option.value"
                                    class="bg-white text-gray-900"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                            <div
                                class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-haven-black"
                            >
                                <svg
                                    class="h-4 w-4 fill-current"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"
                                    />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Custom File Upload -->
                    <div class="group">
                        <label
                            class="mb-1 block text-xs font-bold tracking-wide text-gray-700 uppercase"
                            >Room Image</label
                        >
                        <div class="flex items-center gap-4 pt-1">
                            <label
                                class="cursor-pointer rounded-full bg-white px-5 py-1.5 text-sm font-bold text-blue-600 shadow-sm transition hover:bg-gray-50 active:scale-95"
                            >
                                Browse...
                                <input
                                    type="file"
                                    class="hidden"
                                    accept="image/*"
                                    @change="handleFileChange"
                                />
                            </label>
                            <span
                                class="max-w-[200px] truncate text-sm font-medium text-gray-600"
                            >
                                {{ fileName }}
                            </span>
                        </div>
                        <div
                            v-if="form.errors.image"
                            class="mt-1 text-xs font-bold text-red-600"
                        >
                            {{ form.errors.image }}
                        </div>

                        <!-- Progress Bar -->
                        <div
                            v-if="form.progress"
                            class="mt-2 h-1.5 w-full rounded-full bg-white/30"
                        >
                            <div
                                class="h-1.5 rounded-full bg-blue-600 transition-all duration-300"
                                :style="{
                                    width: form.progress.percentage + '%',
                                }"
                            ></div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end pt-4">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-blue-700 focus:ring-4 focus:ring-blue-500/50 disabled:opacity-70"
                        >
                            <span v-if="form.processing">Saving...</span>
                            <span v-else>Save Changes</span>
                        </button>
                    </div>
                </form>
            </ContentCard>
            <ContentCard
                position="full"
                class="col-span-2"
                title="Reservations"
            >
                <div
                    class="grid grid-cols-2 items-center justify-between rounded-md bg-haven-white p-4 shadow-sm"
                >
                    <div
                        class="grid grid-cols-3 items-center gap-6 text-haven-black"
                    >
                        <input
                            v-model="filterForm.date"
                            type="date"
                            class="form-select"
                        />
                        <select v-model="filterForm.status" class="form-select">
                            <option :value="null">All statuses</option>
                            <option
                                v-for="status in reservationStatusOptions"
                                :key="status.name"
                                :value="status.value"
                            >
                                {{ status.label }}
                            </option>
                        </select>
                    </div>
                    <div class="justify-self-end">
                        <button
                            class="ml-3 text-sm text-gray-500 hover:text-gray-700"
                            type="button"
                            @click="reset"
                        >
                            Reset
                        </button>
                    </div>
                </div>
                <div
                    v-if="reservations.data.length > 0"
                    class="grid grid-cols-1 gap-x-8 gap-y-2 mt-4"
                >
                    <Link
                        v-for="reservation in reservations.data"
                        :key="reservation.id"
                        :href="route('admin.reservations.show', reservation.id)"
                        class="place w-full rounded-2xl bg-haven-white/45 p-3 shadow"
                    >
                        <div class="grid grid-cols-5">
                            <p
                                class="mr-2 line-clamp-1 truncate font-medium text-haven-black"
                            >
                                {{ reservation.name }}
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
                                    new Date(reservation.end_at).toLocaleString(
                                        [],
                                        {
                                            day: '2-digit',
                                            month: '2-digit',
                                            year: 'numeric',
                                            hour: '2-digit',
                                            minute: '2-digit',
                                        },
                                    )
                                }}
                            </p>
                            <p
                                class="w-fit rounded-sm bg-haven-light-blue/50 p-1 text-haven-black shadow"
                            >
                                {{ reservation.user.name }}
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
                        <div class="grid grid-cols-4 text-sm">
                            <p class="text-gray-500">
                                #{{
                                    reservation.id.toString().padStart(5, '0')
                                }}
                            </p>
                            <p class="text-haven-black">
                                {{ reservation.room.name }}
                            </p>
                            <p></p>
                        </div>
                    </Link>
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
    </AdminDashboardLayout>
</template>

<style scoped></style>
