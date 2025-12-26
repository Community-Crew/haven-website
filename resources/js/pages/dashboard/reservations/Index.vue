<script setup lang="ts">
import ContentCard from '@/components/ContentCard.vue';
import Pagination from '@/components/Pagination/Pagination.vue';
import AdminDashboardLayout from '@/layouts/AdminDashboardLayout.vue';
import { Paginator, Reservation, ReservationFilters } from '@/types';
import { Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { ref, watch } from 'vue';

const props = defineProps<{
    reservations: Paginator<Reservation>;
    filters: ReservationFilters;
    rooms: string[];
    statuses: string[];
}>();

const form = ref({
    room: props.filters.room ?? null,
    status: props.filters.status ?? null,
});

watch(
    form,
    debounce(() => {
        router.get('/admin/reservations', form.value, {
            preserveState: true,
            replace: true,
        });
    }, 300),
    { deep: true },
);

const reset = () => {
    form.value = {
        room: null,
        status: null,
    };
};
</script>

<template>
    <AdminDashboardLayout>
        <Link :href="route('admin.reservations.create')"> Create </Link>
        <ContentCard title="Reservations" class="m-6">
            <div class="flex flex-col">
                <div class="relative">
                    <div
                        class="ml-8 flex items-center justify-between rounded-md bg-haven-white p-4 shadow-sm"
                    >
                        <div
                            class="flex w-full max-w-md items-center text-haven-black"
                        >
                            <select v-model="form.room" class="form-select">
                                <option :value="null">All rooms</option>
                                <option
                                    v-for="room in rooms"
                                    :key="room"
                                    :value="room"
                                >
                                    {{ room }}
                                </option>
                            </select>
                            <select v-model="form.status" class="form-select">
                                <option :value="null">All statuses</option>
                                <option
                                    v-for="status in statuses"
                                    :key="status"
                                    :value="status"
                                >
                                    {{ status }}
                                </option>
                            </select>
                        </div>
                        <div>
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
                        class="mt-8 rounded-3xl border-2 border-haven-light-blue bg-haven-light-blue/50 p-8 pt-16"
                    >
                        <div
                            v-if="reservations.data.length > 0"
                            class="grid grid-cols-1 gap-x-8 gap-y-2"
                        >
                            <Link
                                v-for="reservation in reservations.data"
                                :key="reservation.id"
                                :href="route('admin.reservations.show', reservation.id)"
                                class="place w-full rounded-2xl bg-haven-white/45 p-3 shadow"
                            >
                                <div class="grid grid-cols-5">
                                    <p class="font-medium text-haven-black">
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
                                    <p
                                        class="w-fit rounded-sm bg-haven-light-blue/50 p-1 text-haven-black shadow"
                                    >
                                        {{ reservation.user.name }}
                                    </p>
                                    <p
                                        :class="
                                            reservation.status
                                                .background_color +
                                            ' ' +
                                            reservation.status.text_color
                                        "
                                        class="w-20 rounded-2xl p-1 text-center text-sm font-bold"
                                    >
                                        {{ reservation.status.label }}
                                    </p>
                                </div>
                                <hr
                                    class="my-2 border-t border-haven-blue/20"
                                />
                                <div class="grid grid-cols-4 text-sm">
                                    <p class="text-gray-500">
                                        #{{
                                            reservation.id
                                                .toString()
                                                .padStart(5, '0')
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
                    </div>
                </div>
                <Pagination
                    class="justify-center"
                    :links="reservations.links"
                />
            </div>
        </ContentCard>
    </AdminDashboardLayout>
</template>

<style scoped></style>
