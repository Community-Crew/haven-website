<script setup lang="ts">
import LinkButton from '@/components/Buttons/LinkButton.vue';
import ContentCard from '@/components/ContentCard.vue';
import GridContainer from '@/components/Layouts/GridContainer.vue';
import ObjectListEntry from '@/components/Layouts/List/Entries/ObjectListEntry.vue';
import ObjectListLayout from '@/components/Layouts/List/ObjectListLayout.vue';
import Pagination from '@/components/Pagination/Pagination.vue';
import AdminDashboardLayout from '@/layouts/AdminDashboardLayout.vue';
import {
    Paginator,
    Reservation,
    ReservationFilters,
    Room,
    User,
} from '@/types';
import { router } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';
import { getCurrentInstance, ref, watch } from 'vue';
import FunctionButton from '@/components/Buttons/FunctionButton.vue';

const app = getCurrentInstance();
const route = app?.appContext.config.globalProperties.route;

const props = defineProps<{
    target_user: User;
    reservations: Paginator<Reservation>;
    filters: ReservationFilters;
    rooms: Room[];
    statuses: string[];
}>();

const form = ref({
    room: props.filters.room ?? null,
    status: props.filters.status ?? null,
    date: props.filters.date ?? null,
});

watch(
    form,
    debounce(() => {
        router.get(
            route('admin.users.show', props.target_user.id),
            form.value,
            {
                preserveState: true,
                replace: true,
            },
        );
    }, 300),
    { deep: true },
);

const reset = () => {
    form.value = {
        room: null,
        status: null,
        date: null,
    };
};
</script>

<template>
    <AdminDashboardLayout>
        <div>
            <GridContainer cols="4">
                <LinkButton name="Back" :link="route('admin.users.index')" />
                <LinkButton name="Unlink" :link="route('admin.users.index')" />
                <LinkButton
                    name="Delete"
                    :link="route('admin.users.index')"
                    class="bg-haven-red"
                />
            </GridContainer>
            <ContentCard
                position="middle"
                class="mx-6"
                :title="'View: ' + target_user.name"
            >
                <GridContainer cols="2">
                    <div class="w-full">
                        <span
                            class="mb-1 block text-xs font-bold tracking-wide text-gray-700 uppercase"
                        >
                            Name
                        </span>
                        <div
                            class="placeholder-gray/70 w-full rounded-lg border-none bg-white/25 px-4 py-3 text-haven-black shadow-sm focus:bg-white/30 focus:ring-2 focus:ring-white/50"
                        >
                            {{ target_user.name }}
                        </div>
                    </div>
                    <div class="w-full">
                        <span
                            class="mb-1 block text-xs font-bold tracking-wide text-gray-700 uppercase"
                        >
                            E-mail
                        </span>
                        <div
                            class="placeholder-gray/70 w-full rounded-lg border-none bg-white/25 px-4 py-3 text-haven-black shadow-sm focus:bg-white/30 focus:ring-2 focus:ring-white/50"
                        >
                            {{ target_user.email }}
                        </div>
                    </div>

                    <div class="grid w-full grid-cols-2 gap-6">
                        <div>
                            <span
                                class="mb-1 block text-xs font-bold tracking-wide text-gray-700 uppercase"
                            >
                                Created At
                            </span>
                            <div
                                class="placeholder-gray/70 w-full rounded-lg border-none bg-white/25 px-4 py-3 text-haven-black shadow-sm focus:bg-white/30 focus:ring-2 focus:ring-white/50"
                            >
                                {{
                                    new Date(
                                        target_user.created_at,
                                    ).toLocaleDateString()
                                }}
                            </div>
                        </div>
                        <div>
                            <span
                                class="mb-1 block text-xs font-bold tracking-wide text-gray-700 uppercase"
                            >
                                Updated At
                            </span>
                            <div
                                class="placeholder-gray/70 w-full rounded-lg border-none bg-white/25 px-4 py-3 text-haven-black shadow-sm focus:bg-white/30 focus:ring-2 focus:ring-white/50"
                            >
                                {{
                                    new Date(
                                        target_user.updated_at,
                                    ).toLocaleDateString()
                                }}
                            </div>
                        </div>
                    </div>
                    <div class="w-full">
                        <span
                            class="mb-1 block text-xs font-bold tracking-wide text-gray-700 uppercase"
                        >
                            Groups
                        </span>
                        <div class="flex gap-2">
                            <div
                                v-for="group in target_user.groups"
                                :key="group.id"
                                class="placeholder-gray/70 rounded-lg border-none bg-white/25 px-4 py-3 text-haven-black shadow-sm focus:bg-white/30 focus:ring-2 focus:ring-white/50"
                            >
                                {{ group.name }}
                            </div>
                            <div
                                v-if="target_user.groups.length == 0"
                                class="placeholder-gray/70 rounded-lg border-none bg-white/25 px-4 py-3 text-haven-black shadow-sm focus:bg-white/30 focus:ring-2 focus:ring-white/50"
                            >
                                No groups found.
                            </div>
                        </div>
                    </div>

                    <div class="w-full">
                        <span
                            class="mb-1 block text-xs font-bold tracking-wide text-gray-700 uppercase"
                        >
                            Organisations
                        </span>
                        <div class="flex gap-2">
                            <div
                                v-for="organisation in target_user.organisations"
                                :key="organisation.id"
                                class="placeholder-gray/70 rounded-lg border-none bg-white/25 px-4 py-3 text-haven-black shadow-sm focus:bg-white/30 focus:ring-2 focus:ring-white/50"
                            >
                                {{ organisation.name }}
                            </div>
                            <div
                                v-if="target_user.organisations.length == 0"
                                class="placeholder-gray/70 rounded-lg border-none bg-white/25 px-4 py-3 text-haven-black shadow-sm focus:bg-white/30 focus:ring-2 focus:ring-white/50"
                            >
                                No groups found.
                            </div>
                        </div>
                    </div>
                </GridContainer>
            </ContentCard>

            <ContentCard
                class="mx-6 mt-6"
                title="Reservations"
                position="middle"
            >
                <ObjectListLayout>
                    <ObjectListEntry
                        class="grid grid-cols-5 px-4 py-3 font-bold text-haven-white"
                        color="bg-haven-blue"
                    >
                        <div>Name</div>
                        <div>Date</div>
                        <div>Time</div>
                        <div>Organisation</div>
                        <div>Status</div>
                    </ObjectListEntry>
                    <ObjectListEntry class="grid-4 grid">
                        <div
                            class="grid grid-cols-4 items-center gap-6 text-haven-black"
                        >
                            <input
                                v-model="form.date"
                                type="date"
                                class="form-select"
                            />
                            <select v-model="form.room" class="form-select">
                                <option :value="null">All rooms</option>
                                <option
                                    v-for="room in rooms"
                                    :key="room.id"
                                    :value="room.id"
                                >
                                    {{ room.name }}
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
                            <FunctionButton :action="reset" name="Reset filters"/>
                        </div>
                    </ObjectListEntry>
                    <ObjectListEntry
                        v-for="reservation in reservations.data"
                        :key="reservation.id"
                        class="grid grid-cols-5 px-4 py-3 font-bold text-haven-black"
                    >
                        <div>{{ reservation.name }}</div>
                        <div>
                            {{
                                new Date(
                                    reservation.start_at,
                                ).toLocaleDateString()
                            }}
                        </div>
                        <div>
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
                                new Date(reservation.end_at).toLocaleTimeString(
                                    [],
                                    {
                                        hour: '2-digit',
                                        minute: '2-digit',
                                    },
                                )
                            }}
                        </div>
                        <div v-if="reservation.organisation">
                            {{ reservation.organisation.name }}
                        </div>
                        <div v-else>N/A</div>
                        <div
                            :class="
                                        reservation.status.background_color +
                                        ' ' +
                                        reservation.status.text_color
                                    "
                            class="w-20 rounded-2xl p-1 text-center text-sm font-bold"
                        >
                                {{ reservation.status.label }}
                        </div>
                    </ObjectListEntry>
                </ObjectListLayout>
                <Pagination :links="reservations.links" />
            </ContentCard>
        </div>
    </AdminDashboardLayout>
</template>

<style scoped></style>
