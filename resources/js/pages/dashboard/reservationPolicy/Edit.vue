<script setup lang="ts">
import FunctionButton from '@/components/Buttons/FunctionButton.vue';
import LinkButton from '@/components/Buttons/LinkButton.vue';
import ContentCard from '@/components/ContentCard.vue';
import FormLayout from '@/components/Forms/FormLayout.vue';
import MultiSelect from '@/components/Forms/MultiSelect.vue';
import TextInput from '@/components/Forms/TextInput.vue';
import GridContainer from '@/components/Layouts/GridContainer.vue';
import ObjectListEntry from '@/components/Layouts/List/Entries/ObjectListEntry.vue';
import ObjectListLayout from '@/components/Layouts/List/ObjectListLayout.vue';
import AdminDashboardLayout from '@/layouts/AdminDashboardLayout.vue';
import { ReservationPolicy, ReservationPolicyEntry, Room } from '@/types';
import { router, useForm } from '@inertiajs/vue3';
import { getCurrentInstance } from 'vue';

const app = getCurrentInstance();
const route = app?.appContext.config.globalProperties.route;

const props = defineProps<{
    reservationPolicy: ReservationPolicy;
    rooms: Room[];
    reservationPolicyEntries: ReservationPolicyEntry[];
}>();

const form = useForm<{
    role_name: string;
    max_days_in_advance: number;
    room_ids: number[];
}>({
    role_name: props.reservationPolicy.role_name,
    max_days_in_advance: props.reservationPolicy.max_days_in_advance,
    room_ids: props.reservationPolicy.room_ids,
});

function deleteReservationPolicy(reservationPolicy: ReservationPolicy) {
    router.delete(
        route('admin.reservation-policies.destroy', reservationPolicy.id),
    );
}

function deleteReservationPolicyEntry(
    reservationPolicyEntry: ReservationPolicyEntry,
) {
    router.delete(
        route('admin.reservation-policies.entries.destroy', {
            reservation_policy: props.reservationPolicy.id,
            entry: reservationPolicyEntry.id,
        }),
    );
}

const daysOfWeek = [
    { id: 0, name: 'Sunday' },
    { id: 1, name: 'Monday' },
    { id: 2, name: 'Tuesday' },
    { id: 3, name: 'Wednesday' },
    { id: 4, name: 'Thursday' },
    { id: 5, name: 'Friday' },
    { id: 6, name: 'Saturday' },
    { id: 8, name: 'All Week' },
];

const getDayName = (id) => {
    return daysOfWeek.find(day => day.id === id)?.name || 'Unknown';
};

</script>

<template>
    <AdminDashboardLayout>
        <GridContainer cols="3">
            <LinkButton
                :link="
                    route(
                        'admin.reservation-policies.entries.create',
                        props.reservationPolicy.id,
                    )
                "
                name="Add Reservation Rule"
            />
            <FunctionButton
                :action="() => deleteReservationPolicy(reservationPolicy)"
                name="Delete"
                class="bg-haven-red"
            />
        </GridContainer>
        <div class="grid grid-cols-1 gap-6 p-6">
            <ContentCard title="Edit Reservation Policies">
                <FormLayout
                    :form="form"
                    :action="
                        route(
                            'admin.reservation-policies.update',
                            reservationPolicy.id,
                        )
                    "
                    method="put"
                >
                    <TextInput
                        v-model="form.role_name"
                        label="Name"
                        placeholder="default-policy"
                        :error="form.errors.role_name"
                    />

                    <TextInput
                        v-model="form.max_days_in_advance"
                        label="Days in advance"
                        placeholder="14"
                        type="number"
                        :error="form.errors.max_days_in_advance"
                    />

                    <MultiSelect
                        v-model="form.room_ids"
                        label="Rooms"
                        :options="rooms"
                        :error="form.errors.room_ids"
                    />
                </FormLayout>
            </ContentCard>
            <ContentCard title="Reservation Rules">
                <ObjectListLayout>
                    <ObjectListEntry
                        v-for="reservationPolicyEntry in reservationPolicyEntries"
                        v-bind:key="reservationPolicyEntry.id"
                        class="grid grid-cols-4 gap-6 font-bold text-haven-black"
                    >
                        <div class="grid grid-cols-2 items-center gap-6">
                            <span class="text-haven-black"
                                >Day of the week:</span
                            >
                            <div
                                class="flex justify-center rounded-lg bg-haven-light-blue/50 px-4 py-3 text-haven-black shadow-sm"
                            >
                                <span class="font-semibold">
                                    {{
                                        getDayName(reservationPolicyEntry.day_of_week)
                                    }}
                                </span>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 items-center gap-6">
                            <span class="text-haven-black">Start time:</span>
                            <div
                                class="flex justify-center rounded-lg bg-haven-light-blue/50 px-4 py-3 text-haven-black shadow-sm"
                            >
                                <span class="font-semibold">
                                    {{ reservationPolicyEntry.start_time }}
                                </span>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 items-center gap-6">
                            <span class="text-haven-black">End time:</span>
                            <div
                                class="flex justify-center rounded-lg bg-haven-light-blue/50 px-4 py-3 text-haven-black shadow-sm"
                            >
                                <span class="font-semibold">
                                    {{ reservationPolicyEntry.end_time }}
                                </span>
                            </div>
                        </div>
                        <FunctionButton
                            :action="
                                () =>
                                    deleteReservationPolicyEntry(
                                        reservationPolicyEntry,
                                    )
                            "
                            name="Delete"
                            class="bg-haven-red"
                        />
                    </ObjectListEntry>
                </ObjectListLayout>
            </ContentCard>
        </div>
    </AdminDashboardLayout>
</template>

<style scoped></style>
